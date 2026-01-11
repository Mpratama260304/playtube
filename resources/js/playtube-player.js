/**
 * PlayTube Ultra Video Player
 * 
 * High-performance video player with:
 * - HLS.js adaptive bitrate streaming
 * - Automatic quality switching based on bandwidth
 * - Smart buffer management
 * - Network-aware quality selection
 * - Seamless quality transitions
 */

class PlayTubePlayer {
    constructor(options = {}) {
        this.container = null;
        this.video = null;
        this.hls = null;
        
        // Configuration
        this.config = {
            videoId: options.videoId || null,
            streamUrl: options.streamUrl || null,
            hlsUrl: options.hlsUrl || null,
            qualities: options.qualities || [],
            poster: options.poster || null,
            autoplay: options.autoplay || false,
            startTime: options.startTime || 0,
            onReady: options.onReady || (() => {}),
            onPlay: options.onPlay || (() => {}),
            onPause: options.onPause || (() => {}),
            onTimeUpdate: options.onTimeUpdate || (() => {}),
            onQualityChange: options.onQualityChange || (() => {}),
            onError: options.onError || (() => {}),
            onBuffering: options.onBuffering || (() => {}),
            ...options
        };
        
        // State
        this.state = {
            ready: false,
            playing: false,
            buffering: false,
            currentQuality: 'auto',
            availableQualities: [],
            currentLevel: -1,
            bandwidthEstimate: 0,
            networkType: 'unknown',
            bufferLength: 0,
            error: null
        };
        
        // Performance metrics
        this.metrics = {
            startupTime: 0,
            rebufferCount: 0,
            rebufferDuration: 0,
            qualitySwitches: 0,
            droppedFrames: 0,
            loadTime: Date.now()
        };
        
        // HLS.js configuration optimized for performance
        this.hlsConfig = {
            // Enable low latency mode for faster start
            lowLatencyMode: false,
            
            // Buffer settings for smooth playback
            maxBufferLength: 30,
            maxMaxBufferLength: 60,
            maxBufferSize: 60 * 1000 * 1000, // 60MB
            maxBufferHole: 0.5,
            
            // Fast start
            startLevel: -1, // Auto
            autoStartLoad: true,
            startPosition: -1,
            
            // ABR settings
            abrEwmaFastLive: 3,
            abrEwmaSlowLive: 9,
            abrEwmaFastVoD: 3,
            abrEwmaSlowVoD: 9,
            abrBandWidthFactor: 0.95,
            abrBandWidthUpFactor: 0.7,
            abrMaxWithRealBitrate: true,
            
            // Retry settings
            fragLoadingMaxRetry: 4,
            manifestLoadingMaxRetry: 4,
            levelLoadingMaxRetry: 4,
            fragLoadingRetryDelay: 1000,
            manifestLoadingRetryDelay: 1000,
            levelLoadingRetryDelay: 1000,
            
            // Performance
            enableWorker: true,
            enableSoftwareAES: true,
            
            // Debug (disable in production)
            debug: false
        };
    }
    
    /**
     * Initialize the player
     */
    init(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('PlayTubePlayer: Container not found');
            return false;
        }
        
        this.video = this.container.querySelector('video') || this.createVideoElement();
        this.detectNetworkConditions();
        this.setupEventListeners();
        
        // Try HLS first, then fallback to progressive
        if (this.config.hlsUrl && this.isHlsSupported()) {
            this.initHls();
        } else if (this.config.streamUrl) {
            this.initProgressive();
        } else {
            this.handleError('No video source available');
            return false;
        }
        
        return true;
    }
    
    /**
     * Create video element if not exists
     */
    createVideoElement() {
        const video = document.createElement('video');
        video.id = 'video-player';
        video.className = 'w-full h-full';
        video.controls = true;
        video.playsInline = true;
        video.preload = 'metadata';
        video.controlsList = 'nodownload';
        video.oncontextmenu = () => false;
        
        if (this.config.poster) {
            video.poster = this.config.poster;
        }
        
        this.container.appendChild(video);
        return video;
    }
    
    /**
     * Check if HLS.js is supported
     */
    isHlsSupported() {
        return typeof Hls !== 'undefined' && Hls.isSupported();
    }
    
    /**
     * Check if native HLS is supported (Safari)
     */
    isNativeHlsSupported() {
        return this.video && this.video.canPlayType('application/vnd.apple.mpegurl') !== '';
    }
    
    /**
     * Initialize HLS.js
     */
    initHls() {
        if (!this.isHlsSupported()) {
            // Try native HLS for Safari
            if (this.isNativeHlsSupported()) {
                this.video.src = this.config.hlsUrl;
                this.setupNativeHls();
                return;
            }
            
            // Fallback to progressive
            this.initProgressive();
            return;
        }
        
        this.hls = new Hls(this.hlsConfig);
        
        // Setup HLS event listeners
        this.hls.on(Hls.Events.MEDIA_ATTACHED, () => {
            this.hls.loadSource(this.config.hlsUrl);
        });
        
        this.hls.on(Hls.Events.MANIFEST_PARSED, (event, data) => {
            this.handleManifestParsed(data);
        });
        
        this.hls.on(Hls.Events.LEVEL_SWITCHED, (event, data) => {
            this.handleLevelSwitch(data);
        });
        
        this.hls.on(Hls.Events.FRAG_BUFFERED, (event, data) => {
            this.handleFragBuffered(data);
        });
        
        this.hls.on(Hls.Events.ERROR, (event, data) => {
            this.handleHlsError(data);
        });
        
        this.hls.on(Hls.Events.BUFFER_APPENDED, () => {
            this.updateBufferInfo();
        });
        
        // Attach to video element
        this.hls.attachMedia(this.video);
        
        console.log('[PlayTubePlayer] HLS.js initialized');
    }
    
    /**
     * Initialize progressive MP4 streaming
     */
    initProgressive() {
        this.video.src = this.config.streamUrl;
        this.state.availableQualities = this.config.qualities;
        this.state.currentQuality = 'auto';
        
        console.log('[PlayTubePlayer] Progressive streaming initialized');
        
        this.video.addEventListener('loadedmetadata', () => {
            this.setReady();
        });
    }
    
    /**
     * Setup native HLS (Safari)
     */
    setupNativeHls() {
        this.video.addEventListener('loadedmetadata', () => {
            this.setReady();
        });
        
        console.log('[PlayTubePlayer] Native HLS initialized (Safari)');
    }
    
    /**
     * Handle manifest parsed
     */
    handleManifestParsed(data) {
        this.metrics.startupTime = Date.now() - this.metrics.loadTime;
        
        // Build quality list
        this.state.availableQualities = data.levels.map((level, index) => ({
            index,
            height: level.height,
            width: level.width,
            bitrate: level.bitrate,
            label: `${level.height}p`,
            codec: level.videoCodec
        }));
        
        // Add auto option
        this.state.availableQualities.unshift({
            index: -1,
            label: 'Auto',
            bitrate: 0
        });
        
        console.log('[PlayTubePlayer] Manifest parsed', {
            levels: data.levels.length,
            startupTime: this.metrics.startupTime + 'ms'
        });
        
        this.setReady();
        
        // Auto-play if configured
        if (this.config.autoplay) {
            this.play();
        }
        
        // Seek to start time if specified
        if (this.config.startTime > 0) {
            this.video.currentTime = this.config.startTime;
        }
    }
    
    /**
     * Handle level switch
     */
    handleLevelSwitch(data) {
        const level = this.hls.levels[data.level];
        this.state.currentLevel = data.level;
        this.metrics.qualitySwitches++;
        
        console.log('[PlayTubePlayer] Quality switched to', level ? `${level.height}p` : 'auto');
        
        this.config.onQualityChange({
            level: data.level,
            height: level?.height,
            bitrate: level?.bitrate
        });
    }
    
    /**
     * Handle fragment buffered
     */
    handleFragBuffered(data) {
        this.state.buffering = false;
        this.updateBufferInfo();
    }
    
    /**
     * Handle HLS errors
     */
    handleHlsError(data) {
        if (data.fatal) {
            switch (data.type) {
                case Hls.ErrorTypes.NETWORK_ERROR:
                    console.error('[PlayTubePlayer] Network error, attempting recovery...');
                    this.hls.startLoad();
                    break;
                    
                case Hls.ErrorTypes.MEDIA_ERROR:
                    console.error('[PlayTubePlayer] Media error, attempting recovery...');
                    this.hls.recoverMediaError();
                    break;
                    
                default:
                    console.error('[PlayTubePlayer] Fatal error, fallback to progressive');
                    this.hls.destroy();
                    this.initProgressive();
                    break;
            }
        }
        
        this.state.error = data;
        this.config.onError(data);
    }
    
    /**
     * Setup video event listeners
     */
    setupEventListeners() {
        this.video.addEventListener('waiting', () => {
            this.state.buffering = true;
            this.config.onBuffering(true);
        });
        
        this.video.addEventListener('playing', () => {
            this.state.buffering = false;
            this.state.playing = true;
            this.config.onBuffering(false);
            this.config.onPlay();
        });
        
        this.video.addEventListener('pause', () => {
            this.state.playing = false;
            this.config.onPause();
        });
        
        this.video.addEventListener('timeupdate', () => {
            this.config.onTimeUpdate(this.video.currentTime);
        });
        
        this.video.addEventListener('error', (e) => {
            this.handleError(e.target.error);
        });
        
        // Track dropped frames
        if (this.video.getVideoPlaybackQuality) {
            setInterval(() => {
                const quality = this.video.getVideoPlaybackQuality();
                this.metrics.droppedFrames = quality.droppedVideoFrames;
            }, 1000);
        }
    }
    
    /**
     * Detect network conditions
     */
    detectNetworkConditions() {
        if ('connection' in navigator) {
            const conn = navigator.connection;
            this.state.networkType = conn.effectiveType || 'unknown';
            
            conn.addEventListener('change', () => {
                this.state.networkType = conn.effectiveType;
                this.adjustQualityForNetwork();
            });
        }
        
        // Estimate bandwidth from window.performance
        if (window.performance) {
            const entries = performance.getEntriesByType('resource');
            let totalBytes = 0;
            let totalDuration = 0;
            
            entries.forEach(entry => {
                if (entry.transferSize && entry.duration) {
                    totalBytes += entry.transferSize;
                    totalDuration += entry.duration;
                }
            });
            
            if (totalDuration > 0) {
                this.state.bandwidthEstimate = (totalBytes * 8) / (totalDuration / 1000);
            }
        }
    }
    
    /**
     * Adjust quality based on network conditions
     */
    adjustQualityForNetwork() {
        if (!this.hls) return;
        
        const networkQualityMap = {
            'slow-2g': 0,
            '2g': 0,
            '3g': 1,
            '4g': -1 // Auto (best)
        };
        
        const maxLevel = networkQualityMap[this.state.networkType];
        if (maxLevel !== undefined && maxLevel !== -1) {
            this.hls.autoLevelCapping = maxLevel;
        }
    }
    
    /**
     * Update buffer information
     */
    updateBufferInfo() {
        if (this.video.buffered.length > 0) {
            const currentTime = this.video.currentTime;
            let bufferEnd = 0;
            
            for (let i = 0; i < this.video.buffered.length; i++) {
                if (this.video.buffered.start(i) <= currentTime && this.video.buffered.end(i) > currentTime) {
                    bufferEnd = this.video.buffered.end(i);
                    break;
                }
            }
            
            this.state.bufferLength = bufferEnd - currentTime;
        }
    }
    
    /**
     * Set quality level
     */
    setQuality(levelIndex) {
        if (this.hls) {
            if (levelIndex === -1 || levelIndex === 'auto') {
                this.hls.currentLevel = -1;
                this.state.currentQuality = 'auto';
            } else {
                this.hls.currentLevel = levelIndex;
                const level = this.hls.levels[levelIndex];
                this.state.currentQuality = level ? `${level.height}p` : levelIndex;
            }
        } else {
            // Progressive: switch source
            this.switchProgressiveQuality(levelIndex);
        }
    }
    
    /**
     * Switch progressive video quality
     */
    switchProgressiveQuality(quality) {
        const currentTime = this.video.currentTime;
        const wasPlaying = !this.video.paused;
        
        // Find quality URL
        const qualityInfo = this.config.qualities.find(q => q.label === quality || q.index === quality);
        if (qualityInfo && qualityInfo.url) {
            this.video.src = qualityInfo.url;
            
            this.video.addEventListener('loadedmetadata', () => {
                this.video.currentTime = currentTime;
                if (wasPlaying) {
                    this.video.play();
                }
            }, { once: true });
        }
        
        this.state.currentQuality = quality;
    }
    
    /**
     * Get available qualities
     */
    getQualities() {
        return this.state.availableQualities;
    }
    
    /**
     * Get current quality
     */
    getCurrentQuality() {
        if (this.hls) {
            if (this.hls.currentLevel === -1) {
                return 'auto';
            }
            const level = this.hls.levels[this.hls.currentLevel];
            return level ? `${level.height}p` : 'unknown';
        }
        return this.state.currentQuality;
    }
    
    /**
     * Get bandwidth estimate
     */
    getBandwidth() {
        if (this.hls) {
            return this.hls.bandwidthEstimate;
        }
        return this.state.bandwidthEstimate;
    }
    
    /**
     * Set ready state
     */
    setReady() {
        this.state.ready = true;
        this.config.onReady({
            qualities: this.state.availableQualities,
            duration: this.video.duration,
            startupTime: this.metrics.startupTime
        });
    }
    
    /**
     * Handle errors
     */
    handleError(error) {
        console.error('[PlayTubePlayer] Error:', error);
        this.state.error = error;
        this.config.onError(error);
    }
    
    /**
     * Play video
     */
    play() {
        return this.video.play();
    }
    
    /**
     * Pause video
     */
    pause() {
        this.video.pause();
    }
    
    /**
     * Seek to time
     */
    seek(time) {
        this.video.currentTime = time;
    }
    
    /**
     * Get current time
     */
    getCurrentTime() {
        return this.video.currentTime;
    }
    
    /**
     * Get duration
     */
    getDuration() {
        return this.video.duration;
    }
    
    /**
     * Get metrics
     */
    getMetrics() {
        return {
            ...this.metrics,
            currentQuality: this.getCurrentQuality(),
            bandwidth: this.getBandwidth(),
            bufferLength: this.state.bufferLength,
            droppedFrames: this.metrics.droppedFrames
        };
    }
    
    /**
     * Destroy player
     */
    destroy() {
        if (this.hls) {
            this.hls.destroy();
        }
        
        this.video.pause();
        this.video.removeAttribute('src');
        this.video.load();
    }
}

// Alpine.js integration for PlayTube
window.playtubePlayerData = function(config = {}) {
    return {
        player: null,
        ready: false,
        playing: false,
        buffering: false,
        currentQuality: 'auto',
        qualities: [],
        showQualityMenu: false,
        currentTime: 0,
        duration: 0,
        bandwidth: 0,
        error: null,
        
        init() {
            this.player = new PlayTubePlayer({
                ...config,
                onReady: (data) => {
                    this.ready = true;
                    this.qualities = data.qualities;
                    this.duration = data.duration;
                },
                onPlay: () => {
                    this.playing = true;
                },
                onPause: () => {
                    this.playing = false;
                },
                onBuffering: (isBuffering) => {
                    this.buffering = isBuffering;
                },
                onTimeUpdate: (time) => {
                    this.currentTime = time;
                },
                onQualityChange: (data) => {
                    this.currentQuality = data.height ? `${data.height}p` : 'auto';
                },
                onError: (err) => {
                    this.error = err;
                    console.error('Player error:', err);
                }
            });
            
            this.player.init(config.containerId || 'video-player-container');
            
            // Update bandwidth periodically
            setInterval(() => {
                this.bandwidth = this.player.getBandwidth();
            }, 2000);
        },
        
        setQuality(quality) {
            if (quality === 'auto') {
                this.player.setQuality(-1);
            } else {
                const qualityIndex = this.qualities.findIndex(q => q.label === quality);
                if (qualityIndex !== -1) {
                    this.player.setQuality(qualityIndex);
                }
            }
            this.currentQuality = quality;
            this.showQualityMenu = false;
        },
        
        getQualityLabel() {
            if (this.currentQuality === 'auto') {
                const metrics = this.player?.getMetrics();
                return `Auto (${metrics?.currentQuality || '...'})`;
            }
            return this.currentQuality;
        },
        
        formatBandwidth() {
            if (this.bandwidth > 1000000) {
                return `${(this.bandwidth / 1000000).toFixed(1)} Mbps`;
            } else if (this.bandwidth > 1000) {
                return `${(this.bandwidth / 1000).toFixed(0)} Kbps`;
            }
            return `${this.bandwidth} bps`;
        },
        
        destroy() {
            if (this.player) {
                this.player.destroy();
            }
        }
    };
};

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PlayTubePlayer;
}
