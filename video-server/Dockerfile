FROM golang:1.21-alpine AS builder

WORKDIR /app

# Install dependencies
RUN apk add --no-cache git

# Copy go mod files
COPY go.mod go.sum* ./
RUN go mod download

# Copy source code
COPY . .

# Build with optimizations
RUN CGO_ENABLED=0 GOOS=linux go build -ldflags="-w -s" -o video-server .

# Production image
FROM alpine:3.19

WORKDIR /app

# Install ca-certificates for HTTPS
RUN apk --no-cache add ca-certificates

# Copy binary
COPY --from=builder /app/video-server .

# Create non-root user
RUN adduser -D -u 1000 appuser
USER appuser

EXPOSE 8090

ENTRYPOINT ["./video-server"]
