# Mobile Embed Testing Checklist

## Overview

This checklist covers testing embedded video providers (especially Dailymotion) on mobile devices. Use this before releasing changes that affect video playback.

---

## Test Environment Setup

### Required Devices/Emulators
- iOS device or simulator (iPhone/iPad)
- Android device or emulator
- Desktop browser with DevTools mobile emulation (fallback)

### Required Test URLs
- Dailymotion embed video page
- YouTube embed video page
- Vimeo embed video page
- Google Drive embed video page

### Network Conditions to Test
- Normal 4G/WiFi
- Slow 3G (use DevTools network throttling)
- Offline (to verify fallback UI)

---

## Pre-Test Verification

Before testing, verify these changes are deployed:

- [ ] `referrerpolicy="strict-origin-when-cross-origin"` on all non-Google Drive iframes
- [ ] `allow="autoplay; fullscreen; picture-in-picture; encrypted-media"` attribute
- [ ] Embed fallback JavaScript is loaded (check for `embedFallback` in window)
- [ ] HTTPS is enforced on the deployed URL

---

## iOS Safari Testing

### Standard Browsing Mode

| Test Case | Expected Result | Status |
|-----------|-----------------|--------|
| Dailymotion embed loads | Video thumbnail visible | ☐ |
| Play button works | Video starts playing | ☐ |
| Inline playback works | Video plays in-page (not fullscreen) | ☐ |
| Fullscreen button works | Video goes fullscreen | ☐ |
| Audio plays | Sound is audible | ☐ |
| Seeking works | Can skip forward/backward | ☐ |
| Quality selector works | Can change quality (if available) | ☐ |
| Exit fullscreen works | Returns to inline view | ☐ |

### Private Browsing Mode

| Test Case | Expected Result | Status |
|-----------|-----------------|--------|
| Dailymotion embed loads | Video thumbnail visible | ☐ |
| Play button works | Video starts playing | ☐ |
| No cookie/storage errors | Console has no errors | ☐ |

### Low Power Mode

| Test Case | Expected Result | Status |
|-----------|-----------------|--------|
| Embed loads (may not autoplay) | Video thumbnail visible | ☐ |
| Manual play works | Video plays when tapped | ☐ |

---

## Android Chrome Testing

### Standard Browsing Mode

| Test Case | Expected Result | Status |
|-----------|-----------------|--------|
| Dailymotion embed loads | Video thumbnail visible | ☐ |
| Play button works | Video starts playing | ☐ |
| Inline playback works | Video plays in-page | ☐ |
| Fullscreen button works | Video goes fullscreen | ☐ |
| Audio plays | Sound is audible | ☐ |
| Picture-in-Picture works | PiP window appears (if supported) | ☐ |
| Background play restricted | Video pauses when app backgrounded | ☐ |

### Incognito Mode

| Test Case | Expected Result | Status |
|-----------|-----------------|--------|
| Dailymotion embed loads | Video thumbnail visible | ☐ |
| Play button works | Video starts playing | ☐ |
| No third-party cookie errors | Console has no errors | ☐ |

### Data Saver Mode

| Test Case | Expected Result | Status |
|-----------|-----------------|--------|
| Embed loads | Video thumbnail visible | ☐ |
| Video quality may be reduced | Acceptable quality | ☐ |

---

## Slow Network Testing

Use Chrome DevTools → Network → Slow 3G preset

| Test Case | Expected Result | Status |
|-----------|-----------------|--------|
| Page loads within 10 seconds | Content visible | ☐ |
| Embed shows loading state | Loading spinner visible | ☐ |
| Video eventually loads | Thumbnail appears | ☐ |
| Fallback shows if timeout (15s) | "Video couldn't be loaded" message | ☐ |
| "Open on Dailymotion" button works | Opens Dailymotion in new tab | ☐ |

---

## Fallback UI Testing

### Trigger Fallback Manually

1. Open browser DevTools
2. Block network requests to `dailymotion.com`
3. Load a page with Dailymotion embed

| Test Case | Expected Result | Status |
|-----------|-----------------|--------|
| Fallback UI shows after 15s | Error message visible | ☐ |
| Error message is user-friendly | "Video couldn't be loaded" | ☐ |
| "Open on Dailymotion" button visible | Button is clickable | ☐ |
| Button links to correct URL | Opens video on Dailymotion | ☐ |
| No JavaScript errors | Console is clean | ☐ |

### URL Validation Testing

Test with invalid embed URLs (should show fallback):

| Invalid URL | Expected Result |
|-------------|-----------------|
| `http://evil.com/embed/video/abc` | Fallback shown |
| `https://dailymotion.com.evil.com/...` | Fallback shown |
| `javascript:alert(1)` | Fallback shown |
| Empty URL | Fallback shown |

---

## Cross-Platform Provider Testing

### YouTube Embeds

| Platform | Loads | Plays | Fullscreen | Status |
|----------|-------|-------|------------|--------|
| iOS Safari | ☐ | ☐ | ☐ | |
| iOS Private | ☐ | ☐ | ☐ | |
| Android Chrome | ☐ | ☐ | ☐ | |
| Android Incognito | ☐ | ☐ | ☐ | |

### Vimeo Embeds

| Platform | Loads | Plays | Fullscreen | Status |
|----------|-------|-------|------------|--------|
| iOS Safari | ☐ | ☐ | ☐ | |
| iOS Private | ☐ | ☐ | ☐ | |
| Android Chrome | ☐ | ☐ | ☐ | |
| Android Incognito | ☐ | ☐ | ☐ | |

### Google Drive Embeds

| Platform | Loads | Plays | Fullscreen | Status |
|----------|-------|-------|------------|--------|
| iOS Safari | ☐ | ☐ | ☐ | |
| iOS Private | ☐ | ☐ | ☐ | |
| Android Chrome | ☐ | ☐ | ☐ | |
| Android Incognito | ☐ | ☐ | ☐ | |

---

## Regression Testing

After any embed-related changes, verify:

| Test Case | Status |
|-----------|--------|
| Uploaded videos still play | ☐ |
| HLS streaming works (if enabled) | ☐ |
| Quality selector works | ☐ |
| View counting still works | ☐ |
| Like/dislike buttons work | ☐ |
| Comments load | ☐ |
| Share modal works | ☐ |

---

## Bug Report Template

If you find an issue, document it with:

```markdown
## Bug: [Brief Description]

### Environment
- Device: [e.g., iPhone 13, Pixel 6]
- OS: [e.g., iOS 17.1, Android 14]
- Browser: [e.g., Safari, Chrome 120]
- Browsing Mode: [Normal/Private]
- Network: [WiFi/4G/Slow 3G]

### Video Details
- Embed Provider: [Dailymotion/YouTube/etc]
- Video URL: [the embedded video URL]

### Steps to Reproduce
1. 
2. 
3. 

### Expected Behavior
[What should happen]

### Actual Behavior
[What actually happened]

### Screenshots/Videos
[Attach if possible]

### Console Errors
[Paste any JavaScript errors]
```

---

## Sign-off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Developer | | | ☐ |
| QA | | | ☐ |
| Product | | | ☐ |
