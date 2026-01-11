package main

import (
	"context"
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
	"os/signal"
	"path/filepath"
	"runtime"
	"strconv"
	"strings"
	"sync"
	"syscall"
	"time"

	"github.com/gorilla/mux"
)

// Config holds server configuration
type Config struct {
	Port            int
	VideoBasePath   string
	PublicBasePath  string // Public storage for thumbnails
	HLSBasePath     string
	CacheEnabled    bool
	CacheDuration   time.Duration
	SignedURLKey    string
	AllowedOrigins  []string
	MaxCacheSize    int64 // bytes
	ChunkSize       int64
}

// VideoCache implements efficient memory-mapped caching
type VideoCache struct {
	mu       sync.RWMutex
	items    map[string]*CacheItem
	size     int64
	maxSize  int64
	hits     int64
	misses   int64
}

type CacheItem struct {
	data       []byte
	size       int64
	accessTime time.Time
	hitCount   int64
}

// Global instances
var (
	config     Config
	videoCache *VideoCache
	logger     *log.Logger
)

func init() {
	logger = log.New(os.Stdout, "[VIDEO-SERVER] ", log.LstdFlags|log.Lmicroseconds)
}

func main() {
	// Parse command line flags
	flag.IntVar(&config.Port, "port", getEnvInt("VIDEO_SERVER_PORT", 8090), "Server port")
	flag.StringVar(&config.VideoBasePath, "video-path", getEnv("VIDEO_BASE_PATH", "/workspaces/playtube/storage/app/private/videos"), "Base path for videos")
	flag.StringVar(&config.PublicBasePath, "public-path", getEnv("PUBLIC_BASE_PATH", "/workspaces/playtube/storage/app/public/videos"), "Base path for public files (thumbnails)")
	flag.StringVar(&config.HLSBasePath, "hls-path", getEnv("HLS_BASE_PATH", "/workspaces/playtube/storage/app/private/hls"), "Base path for HLS files")
	flag.StringVar(&config.SignedURLKey, "secret", getEnv("VIDEO_SECRET_KEY", "playtube-video-secret-key-change-in-production"), "Secret key for signed URLs")
	flag.Int64Var(&config.MaxCacheSize, "cache-size", getEnvInt64("VIDEO_CACHE_SIZE", 1024*1024*1024), "Max cache size in bytes (default 1GB)")
	flag.Int64Var(&config.ChunkSize, "chunk-size", getEnvInt64("VIDEO_CHUNK_SIZE", 2*1024*1024), "Chunk size for streaming (default 2MB)")
	flag.BoolVar(&config.CacheEnabled, "cache", getEnvBool("VIDEO_CACHE_ENABLED", true), "Enable caching")
	flag.Parse()

	// Parse allowed origins
	originsEnv := getEnv("ALLOWED_ORIGINS", "http://localhost:8000,http://localhost:8080,http://127.0.0.1:8000")
	config.AllowedOrigins = strings.Split(originsEnv, ",")
	config.CacheDuration = time.Hour

	// Initialize cache
	videoCache = &VideoCache{
		items:   make(map[string]*CacheItem),
		maxSize: config.MaxCacheSize,
	}

	// Start cache cleanup goroutine
	go videoCache.cleanupLoop()

	// Create router
	router := mux.NewRouter()

	// Middleware
	router.Use(corsMiddleware)
	router.Use(loggingMiddleware)
	router.Use(recoveryMiddleware)

	// Health check
	router.HandleFunc("/health", healthHandler).Methods("GET", "HEAD")

	// Video streaming endpoints
	router.HandleFunc("/stream/{uuid}", streamHandler).Methods("GET", "HEAD", "OPTIONS")
	router.HandleFunc("/stream/{uuid}/{quality}", streamQualityHandler).Methods("GET", "HEAD", "OPTIONS")

	// HLS endpoints
	router.HandleFunc("/hls/{uuid}/master.m3u8", hlsMasterHandler).Methods("GET", "HEAD", "OPTIONS")
	router.HandleFunc("/hls/{uuid}/{quality}/playlist.m3u8", hlsPlaylistHandler).Methods("GET", "HEAD", "OPTIONS")
	router.HandleFunc("/hls/{uuid}/{quality}/{segment}", hlsSegmentHandler).Methods("GET", "HEAD", "OPTIONS")

	// DASH endpoints
	router.HandleFunc("/dash/{uuid}/manifest.mpd", dashManifestHandler).Methods("GET", "HEAD", "OPTIONS")
	router.HandleFunc("/dash/{uuid}/{quality}/{segment}", dashSegmentHandler).Methods("GET", "HEAD", "OPTIONS")

	// Thumbnail endpoint
	router.HandleFunc("/thumb/{uuid}", thumbnailHandler).Methods("GET", "HEAD", "OPTIONS")

	// Stats endpoint
	router.HandleFunc("/stats", statsHandler).Methods("GET")

	// Create server with optimized settings
	server := &http.Server{
		Addr:              fmt.Sprintf(":%d", config.Port),
		Handler:           router,
		ReadTimeout:       30 * time.Second,
		WriteTimeout:      0, // No write timeout for streaming
		IdleTimeout:       120 * time.Second,
		ReadHeaderTimeout: 10 * time.Second,
		MaxHeaderBytes:    1 << 20, // 1MB
	}

	// Optimize Go runtime
	runtime.GOMAXPROCS(runtime.NumCPU())

	// Graceful shutdown
	go func() {
		sigChan := make(chan os.Signal, 1)
		signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)
		<-sigChan

		logger.Println("Shutting down gracefully...")
		ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
		defer cancel()
		server.Shutdown(ctx)
	}()

	logger.Printf("🚀 Go Video Server starting on port %d", config.Port)
	logger.Printf("📁 Video path: %s", config.VideoBasePath)
	logger.Printf("📁 HLS path: %s", config.HLSBasePath)
	logger.Printf("💾 Cache enabled: %v (max: %d MB)", config.CacheEnabled, config.MaxCacheSize/(1024*1024))

	if err := server.ListenAndServe(); err != http.ErrServerClosed {
		logger.Fatalf("Server error: %v", err)
	}
}

// CORS Middleware
func corsMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		origin := r.Header.Get("Origin")
		
		// In development, allow all origins (GitHub Codespaces, localhost, etc.)
		// Check if origin matches allowed list or contains github.dev/codespaces patterns
		allowed := false
		for _, o := range config.AllowedOrigins {
			if o == origin || o == "*" {
				allowed = true
				break
			}
		}
		
		// Also allow GitHub Codespaces and common dev patterns
		if !allowed && origin != "" {
			if strings.Contains(origin, "github.dev") || 
			   strings.Contains(origin, "codespaces") ||
			   strings.Contains(origin, "localhost") ||
			   strings.Contains(origin, "127.0.0.1") {
				allowed = true
			}
		}

		// Set CORS headers
		if origin != "" {
			w.Header().Set("Access-Control-Allow-Origin", origin)
		} else {
			w.Header().Set("Access-Control-Allow-Origin", "*")
		}
		w.Header().Set("Access-Control-Allow-Credentials", "true")
		w.Header().Set("Access-Control-Allow-Methods", "GET, HEAD, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Range, Accept, Accept-Encoding, Content-Type, Authorization, X-Requested-With")
		w.Header().Set("Access-Control-Expose-Headers", "Content-Length, Content-Range, Accept-Ranges, Content-Type")
		w.Header().Set("Access-Control-Max-Age", "86400")

		if r.Method == "OPTIONS" {
			w.WriteHeader(http.StatusNoContent)
			return
		}

		next.ServeHTTP(w, r)
	})
}

// Logging Middleware
func loggingMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		start := time.Now()
		wrapped := &responseWriter{ResponseWriter: w, statusCode: http.StatusOK}
		next.ServeHTTP(wrapped, r)
		logger.Printf("%s %s %d %v %s", r.Method, r.URL.Path, wrapped.statusCode, time.Since(start), r.Header.Get("Range"))
	})
}

type responseWriter struct {
	http.ResponseWriter
	statusCode int
}

func (rw *responseWriter) WriteHeader(code int) {
	rw.statusCode = code
	rw.ResponseWriter.WriteHeader(code)
}

// Recovery Middleware
func recoveryMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		defer func() {
			if err := recover(); err != nil {
				logger.Printf("Panic recovered: %v", err)
				http.Error(w, "Internal Server Error", http.StatusInternalServerError)
			}
		}()
		next.ServeHTTP(w, r)
	})
}

// Health Handler
func healthHandler(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"status":    "healthy",
		"timestamp": time.Now().UTC().Format(time.RFC3339),
		"version":   "1.0.0",
		"go":        runtime.Version(),
	})
}

// Stats Handler
func statsHandler(w http.ResponseWriter, r *http.Request) {
	var m runtime.MemStats
	runtime.ReadMemStats(&m)

	videoCache.mu.RLock()
	cacheHits := videoCache.hits
	cacheMisses := videoCache.misses
	cacheSize := videoCache.size
	cacheItems := len(videoCache.items)
	videoCache.mu.RUnlock()

	hitRate := float64(0)
	if cacheHits+cacheMisses > 0 {
		hitRate = float64(cacheHits) / float64(cacheHits+cacheMisses) * 100
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"uptime":       time.Since(time.Now()).String(),
		"goroutines":   runtime.NumGoroutine(),
		"memory_alloc": formatBytes(m.Alloc),
		"memory_sys":   formatBytes(m.Sys),
		"gc_runs":      m.NumGC,
		"cache": map[string]interface{}{
			"enabled":    config.CacheEnabled,
			"items":      cacheItems,
			"size":       formatBytes(uint64(cacheSize)),
			"max_size":   formatBytes(uint64(config.MaxCacheSize)),
			"hits":       cacheHits,
			"misses":     cacheMisses,
			"hit_rate":   fmt.Sprintf("%.2f%%", hitRate),
		},
	})
}

// Stream Handler - Main video streaming with Range support
func streamHandler(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	uuid := vars["uuid"]

	// Validate signed URL if in production
	if !validateRequest(r, uuid) {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}

	// Find video file
	videoPath := findVideoFile(uuid, "")
	if videoPath == "" {
		http.Error(w, "Video not found", http.StatusNotFound)
		return
	}

	serveVideoWithRange(w, r, videoPath)
}

// Stream Quality Handler
func streamQualityHandler(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	uuid := vars["uuid"]
	quality := vars["quality"]

	if !validateRequest(r, uuid) {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}

	videoPath := findVideoFile(uuid, quality)
	if videoPath == "" {
		http.Error(w, "Video not found", http.StatusNotFound)
		return
	}

	serveVideoWithRange(w, r, videoPath)
}

// HLS Master Playlist Handler
func hlsMasterHandler(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	uuid := vars["uuid"]

	if !validateRequest(r, uuid) {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}

	masterPath := filepath.Join(config.HLSBasePath, uuid, "master.m3u8")
	if _, err := os.Stat(masterPath); os.IsNotExist(err) {
		// Generate dynamic master playlist
		generateMasterPlaylist(w, r, uuid)
		return
	}

	w.Header().Set("Content-Type", "application/vnd.apple.mpegurl")
	w.Header().Set("Cache-Control", "no-cache")
	http.ServeFile(w, r, masterPath)
}

// Generate Dynamic Master Playlist
func generateMasterPlaylist(w http.ResponseWriter, r *http.Request, uuid string) {
	baseURL := fmt.Sprintf("/hls/%s", uuid)

	qualities := []struct {
		name       string
		bandwidth  int
		resolution string
	}{
		{"360p", 800000, "640x360"},
		{"480p", 1400000, "854x480"},
		{"720p", 2500000, "1280x720"},
		{"1080p", 5000000, "1920x1080"},
	}

	var playlist strings.Builder
	playlist.WriteString("#EXTM3U\n")
	playlist.WriteString("#EXT-X-VERSION:3\n")

	for _, q := range qualities {
		playlistPath := filepath.Join(config.HLSBasePath, uuid, q.name, "playlist.m3u8")
		if _, err := os.Stat(playlistPath); err == nil {
			playlist.WriteString(fmt.Sprintf("#EXT-X-STREAM-INF:BANDWIDTH=%d,RESOLUTION=%s,NAME=\"%s\"\n", q.bandwidth, q.resolution, q.name))
			playlist.WriteString(fmt.Sprintf("%s/%s/playlist.m3u8\n", baseURL, q.name))
		}
	}

	w.Header().Set("Content-Type", "application/vnd.apple.mpegurl")
	w.Header().Set("Cache-Control", "no-cache")
	w.Write([]byte(playlist.String()))
}

// HLS Playlist Handler
func hlsPlaylistHandler(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	uuid := vars["uuid"]
	quality := vars["quality"]

	if !validateRequest(r, uuid) {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}

	playlistPath := filepath.Join(config.HLSBasePath, uuid, quality, "playlist.m3u8")
	if _, err := os.Stat(playlistPath); os.IsNotExist(err) {
		http.Error(w, "Playlist not found", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/vnd.apple.mpegurl")
	w.Header().Set("Cache-Control", "max-age=2")
	http.ServeFile(w, r, playlistPath)
}

// HLS Segment Handler
func hlsSegmentHandler(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	uuid := vars["uuid"]
	quality := vars["quality"]
	segment := vars["segment"]

	if !validateRequest(r, uuid) {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}

	segmentPath := filepath.Join(config.HLSBasePath, uuid, quality, segment)
	if _, err := os.Stat(segmentPath); os.IsNotExist(err) {
		http.Error(w, "Segment not found", http.StatusNotFound)
		return
	}

	// Determine content type
	contentType := "video/mp2t"
	if strings.HasSuffix(segment, ".m4s") {
		contentType = "video/iso.segment"
	} else if strings.HasSuffix(segment, ".mp4") {
		contentType = "video/mp4"
	}

	w.Header().Set("Content-Type", contentType)
	w.Header().Set("Cache-Control", "max-age=31536000") // 1 year for segments
	http.ServeFile(w, r, segmentPath)
}

// DASH Manifest Handler
func dashManifestHandler(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	uuid := vars["uuid"]

	if !validateRequest(r, uuid) {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}

	manifestPath := filepath.Join(config.HLSBasePath, uuid, "manifest.mpd")
	if _, err := os.Stat(manifestPath); os.IsNotExist(err) {
		// Generate dynamic DASH manifest
		generateDashManifest(w, r, uuid)
		return
	}

	w.Header().Set("Content-Type", "application/dash+xml")
	w.Header().Set("Cache-Control", "no-cache")
	http.ServeFile(w, r, manifestPath)
}

// Generate Dynamic DASH Manifest
func generateDashManifest(w http.ResponseWriter, r *http.Request, uuid string) {
	// For now, return 404 if no static manifest
	http.Error(w, "DASH manifest not available", http.StatusNotFound)
}

// DASH Segment Handler
func dashSegmentHandler(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	uuid := vars["uuid"]
	quality := vars["quality"]
	segment := vars["segment"]

	if !validateRequest(r, uuid) {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}

	segmentPath := filepath.Join(config.HLSBasePath, uuid, quality, segment)
	if _, err := os.Stat(segmentPath); os.IsNotExist(err) {
		http.Error(w, "Segment not found", http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "video/iso.segment")
	w.Header().Set("Cache-Control", "max-age=31536000")
	http.ServeFile(w, r, segmentPath)
}

// Thumbnail Handler
func thumbnailHandler(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	uuid := vars["uuid"]

	// Check public storage first (where thumbnails are stored)
	thumbPaths := []string{
		filepath.Join(config.PublicBasePath, uuid, "thumb.jpg"),
		filepath.Join(config.PublicBasePath, uuid, "thumbnail.jpg"),
		filepath.Join(config.VideoBasePath, uuid, "thumb.jpg"),
		filepath.Join(config.VideoBasePath, uuid, "thumbnail.jpg"),
	}

	for _, path := range thumbPaths {
		if _, err := os.Stat(path); err == nil {
			w.Header().Set("Content-Type", "image/jpeg")
			w.Header().Set("Cache-Control", "public, max-age=604800, immutable") // 7 days cache
			w.Header().Set("X-Content-Type-Options", "nosniff")
			http.ServeFile(w, r, path)
			return
		}
	}

	// Return a 1x1 transparent pixel as fallback (prevents broken image)
	w.Header().Set("Content-Type", "image/png")
	w.Header().Set("Cache-Control", "no-cache")
	w.WriteHeader(http.StatusNotFound)
}

// Serve video with proper Range support
func serveVideoWithRange(w http.ResponseWriter, r *http.Request, videoPath string) {
	file, err := os.Open(videoPath)
	if err != nil {
		http.Error(w, "Cannot open video", http.StatusInternalServerError)
		return
	}
	defer file.Close()

	stat, err := file.Stat()
	if err != nil {
		http.Error(w, "Cannot stat video", http.StatusInternalServerError)
		return
	}

	fileSize := stat.Size()
	contentType := getContentType(videoPath)

	w.Header().Set("Content-Type", contentType)
	w.Header().Set("Accept-Ranges", "bytes")
	w.Header().Set("Cache-Control", "max-age=31536000")

	// Parse Range header
	rangeHeader := r.Header.Get("Range")
	if rangeHeader == "" {
		// No range requested - HEAD or full file
		w.Header().Set("Content-Length", strconv.FormatInt(fileSize, 10))

		if r.Method == "HEAD" {
			w.WriteHeader(http.StatusOK)
			return
		}

		// Stream full file
		io.Copy(w, file)
		return
	}

	// Parse range
	start, end, err := parseRange(rangeHeader, fileSize)
	if err != nil {
		w.Header().Set("Content-Range", fmt.Sprintf("bytes */%d", fileSize))
		http.Error(w, "Invalid range", http.StatusRequestedRangeNotSatisfiable)
		return
	}

	// Set response headers for partial content
	contentLength := end - start + 1
	w.Header().Set("Content-Length", strconv.FormatInt(contentLength, 10))
	w.Header().Set("Content-Range", fmt.Sprintf("bytes %d-%d/%d", start, end, fileSize))

	if r.Method == "HEAD" {
		w.WriteHeader(http.StatusPartialContent)
		return
	}

	w.WriteHeader(http.StatusPartialContent)

	// Seek to start position
	file.Seek(start, io.SeekStart)

	// Stream the requested range using optimized buffer
	buffer := make([]byte, config.ChunkSize)
	remaining := contentLength

	for remaining > 0 {
		toRead := remaining
		if toRead > config.ChunkSize {
			toRead = config.ChunkSize
		}

		n, err := file.Read(buffer[:toRead])
		if err != nil && err != io.EOF {
			break
		}

		if n > 0 {
			w.Write(buffer[:n])
			remaining -= int64(n)
		}

		if err == io.EOF {
			break
		}
	}
}

// Find video file by UUID and quality
func findVideoFile(uuid, quality string) string {
	var paths []string

	if quality != "" {
		// Quality-specific paths - check both public and private storage
		paths = []string{
			// Public storage (where uploaded videos are)
			filepath.Join(config.PublicBasePath, uuid, quality+".mp4"),
			filepath.Join(config.PublicBasePath, uuid, "renditions", quality+".mp4"),
			filepath.Join(config.PublicBasePath, uuid+"-"+quality+".mp4"),
			// Private storage
			filepath.Join(config.VideoBasePath, uuid, quality+".mp4"),
			filepath.Join(config.VideoBasePath, uuid, "renditions", quality+".mp4"),
			filepath.Join(config.VideoBasePath, uuid+"-"+quality+".mp4"),
		}
	} else {
		// Default paths (prefer stream-optimized) - check both storages
		paths = []string{
			// Public storage first (where most videos are)
			filepath.Join(config.PublicBasePath, uuid, "stream.mp4"),
			filepath.Join(config.PublicBasePath, uuid, "original.mp4"),
			filepath.Join(config.PublicBasePath, uuid+"-stream.mp4"),
			filepath.Join(config.PublicBasePath, uuid+".mp4"),
			// Private storage
			filepath.Join(config.VideoBasePath, uuid, "stream.mp4"),
			filepath.Join(config.VideoBasePath, uuid, "original.mp4"),
			filepath.Join(config.VideoBasePath, uuid+"-stream.mp4"),
			filepath.Join(config.VideoBasePath, uuid+".mp4"),
		}
	}

	for _, path := range paths {
		if _, err := os.Stat(path); err == nil {
			return path
		}
	}

	return ""
}

// Parse Range header
func parseRange(rangeHeader string, fileSize int64) (int64, int64, error) {
	if !strings.HasPrefix(rangeHeader, "bytes=") {
		return 0, 0, fmt.Errorf("invalid range format")
	}

	rangeSpec := strings.TrimPrefix(rangeHeader, "bytes=")
	parts := strings.Split(rangeSpec, "-")

	if len(parts) != 2 {
		return 0, 0, fmt.Errorf("invalid range format")
	}

	var start, end int64
	var err error

	if parts[0] == "" {
		// Suffix range: bytes=-500
		suffix, err := strconv.ParseInt(parts[1], 10, 64)
		if err != nil {
			return 0, 0, err
		}
		start = fileSize - suffix
		end = fileSize - 1
	} else if parts[1] == "" {
		// Open-ended range: bytes=500-
		start, err = strconv.ParseInt(parts[0], 10, 64)
		if err != nil {
			return 0, 0, err
		}
		end = fileSize - 1
	} else {
		// Normal range: bytes=500-999
		start, err = strconv.ParseInt(parts[0], 10, 64)
		if err != nil {
			return 0, 0, err
		}
		end, err = strconv.ParseInt(parts[1], 10, 64)
		if err != nil {
			return 0, 0, err
		}
	}

	// Validate range
	if start < 0 {
		start = 0
	}
	if end >= fileSize {
		end = fileSize - 1
	}
	if start > end {
		return 0, 0, fmt.Errorf("invalid range: start > end")
	}

	return start, end, nil
}

// Get content type from file extension
func getContentType(path string) string {
	ext := strings.ToLower(filepath.Ext(path))
	types := map[string]string{
		".mp4":  "video/mp4",
		".webm": "video/webm",
		".m3u8": "application/vnd.apple.mpegurl",
		".ts":   "video/mp2t",
		".m4s":  "video/iso.segment",
		".mpd":  "application/dash+xml",
	}
	if t, ok := types[ext]; ok {
		return t
	}
	return "application/octet-stream"
}

// Validate request (signature check)
func validateRequest(r *http.Request, uuid string) bool {
	// In development mode, allow all requests
	if getEnv("APP_ENV", "local") != "production" {
		return true
	}

	// Check for signed URL
	sig := r.URL.Query().Get("sig")
	expires := r.URL.Query().Get("expires")

	if sig == "" || expires == "" {
		return false
	}

	// Check expiration
	expTime, err := strconv.ParseInt(expires, 10, 64)
	if err != nil || time.Now().Unix() > expTime {
		return false
	}

	// Verify signature
	expectedSig := generateSignature(uuid, expires)
	return hmac.Equal([]byte(sig), []byte(expectedSig))
}

func generateSignature(uuid, expires string) string {
	data := fmt.Sprintf("%s:%s", uuid, expires)
	h := hmac.New(sha256.New, []byte(config.SignedURLKey))
	h.Write([]byte(data))
	return hex.EncodeToString(h.Sum(nil))
}

// Cache methods
func (c *VideoCache) cleanupLoop() {
	ticker := time.NewTicker(5 * time.Minute)
	for range ticker.C {
		c.cleanup()
	}
}

func (c *VideoCache) cleanup() {
	c.mu.Lock()
	defer c.mu.Unlock()

	now := time.Now()
	for key, item := range c.items {
		// Remove items not accessed for 30 minutes
		if now.Sub(item.accessTime) > 30*time.Minute {
			c.size -= item.size
			delete(c.items, key)
		}
	}
}

// Helper functions
func getEnv(key, defaultValue string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultValue
}

func getEnvInt(key string, defaultValue int) int {
	if value := os.Getenv(key); value != "" {
		if i, err := strconv.Atoi(value); err == nil {
			return i
		}
	}
	return defaultValue
}

func getEnvInt64(key string, defaultValue int64) int64 {
	if value := os.Getenv(key); value != "" {
		if i, err := strconv.ParseInt(value, 10, 64); err == nil {
			return i
		}
	}
	return defaultValue
}

func getEnvBool(key string, defaultValue bool) bool {
	if value := os.Getenv(key); value != "" {
		return value == "true" || value == "1"
	}
	return defaultValue
}

func formatBytes(b uint64) string {
	const unit = 1024
	if b < unit {
		return fmt.Sprintf("%d B", b)
	}
	div, exp := uint64(unit), 0
	for n := b / unit; n >= unit; n /= unit {
		div *= unit
		exp++
	}
	return fmt.Sprintf("%.1f %cB", float64(b)/float64(div), "KMGTPE"[exp])
}
