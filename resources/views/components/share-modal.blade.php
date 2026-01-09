@props(['video'])

<div 
    x-data="shareModal()"
    x-show="open"
    x-cloak
    @open-share-modal.window="openModal($event.detail)"
    @keydown.escape.window="closeModal()"
    class="fixed inset-0 z-[70] flex items-center justify-center"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closeModal()"></div>

    <!-- Modal Content -->
    <div 
        class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden"
        @click.stop
        x-transition:enter="transition ease-out duration-200 delay-75"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Share</h3>
            <button 
                @click="closeModal()" 
                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Social Share Buttons -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-center gap-3 flex-wrap">
                <!-- Twitter/X -->
                <button 
                    @click="shareToSocial('twitter')"
                    class="flex flex-col items-center gap-1 p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group"
                    title="Share on X (Twitter)"
                >
                    <div class="w-12 h-12 flex items-center justify-center rounded-full bg-black text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">X</span>
                </button>

                <!-- Facebook -->
                <button 
                    @click="shareToSocial('facebook')"
                    class="flex flex-col items-center gap-1 p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group"
                    title="Share on Facebook"
                >
                    <div class="w-12 h-12 flex items-center justify-center rounded-full bg-[#1877F2] text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Facebook</span>
                </button>

                <!-- WhatsApp -->
                <button 
                    @click="shareToSocial('whatsapp')"
                    class="flex flex-col items-center gap-1 p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group"
                    title="Share on WhatsApp"
                >
                    <div class="w-12 h-12 flex items-center justify-center rounded-full bg-[#25D366] text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">WhatsApp</span>
                </button>

                <!-- Telegram -->
                <button 
                    @click="shareToSocial('telegram')"
                    class="flex flex-col items-center gap-1 p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group"
                    title="Share on Telegram"
                >
                    <div class="w-12 h-12 flex items-center justify-center rounded-full bg-[#0088cc] text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Telegram</span>
                </button>

                <!-- Reddit -->
                <button 
                    @click="shareToSocial('reddit')"
                    class="flex flex-col items-center gap-1 p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group"
                    title="Share on Reddit"
                >
                    <div class="w-12 h-12 flex items-center justify-center rounded-full bg-[#FF4500] text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Reddit</span>
                </button>

                <!-- Email -->
                <button 
                    @click="shareToSocial('email')"
                    class="flex flex-col items-center gap-1 p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group"
                    title="Share via Email"
                >
                    <div class="w-12 h-12 flex items-center justify-center rounded-full bg-gray-600 text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Email</span>
                </button>
            </div>
        </div>

        <!-- Copy Link Section -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <input 
                    type="text" 
                    x-model="shareUrl"
                    readonly
                    class="flex-1 px-3 py-2 text-sm bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 select-all"
                    @click="$el.select()"
                >
                <button 
                    @click="copyLink()"
                    class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-200 transition-colors flex items-center gap-2"
                >
                    <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                </button>
            </div>
        </div>

        <!-- Timestamp Option (only for watch page) -->
        <div class="p-4" x-show="showTimestamp">
            <label class="flex items-center gap-3 cursor-pointer">
                <input 
                    type="checkbox" 
                    x-model="includeTimestamp"
                    @change="updateShareUrl()"
                    class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-brand-500 focus:ring-brand-500 dark:bg-gray-800"
                >
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    Start at <span x-text="formatTimestamp(currentTime)" class="font-mono font-medium"></span>
                </span>
            </label>
        </div>
    </div>
</div>

<script>
function shareModal() {
    return {
        open: false,
        videoTitle: '',
        videoUrl: '',
        shareUrl: '',
        currentTime: 0,
        includeTimestamp: false,
        showTimestamp: false,
        copied: false,

        openModal(detail = {}) {
            this.videoTitle = detail.title || document.title;
            this.videoUrl = detail.url || window.location.href;
            this.currentTime = detail.currentTime || 0;
            this.showTimestamp = detail.showTimestamp || false;
            this.includeTimestamp = false;
            this.updateShareUrl();
            this.open = true;
        },

        closeModal() {
            this.open = false;
            this.copied = false;
        },

        updateShareUrl() {
            let url = new URL(this.videoUrl);
            if (this.includeTimestamp && this.currentTime > 0) {
                url.searchParams.set('t', Math.floor(this.currentTime));
            } else {
                url.searchParams.delete('t');
            }
            this.shareUrl = url.toString();
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.shareUrl);
                this.copied = true;
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Link copied to clipboard', type: 'success' }
                }));
                setTimeout(() => { this.copied = false; }, 2000);
            } catch (e) {
                // Fallback for older browsers
                const input = document.createElement('input');
                input.value = this.shareUrl;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                this.copied = true;
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Link copied to clipboard', type: 'success' }
                }));
                setTimeout(() => { this.copied = false; }, 2000);
            }
        },

        shareToSocial(platform) {
            const encodedUrl = encodeURIComponent(this.shareUrl);
            const encodedTitle = encodeURIComponent(this.videoTitle);
            let shareLink = '';

            switch (platform) {
                case 'twitter':
                    shareLink = `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`;
                    break;
                case 'facebook':
                    shareLink = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
                    break;
                case 'whatsapp':
                    shareLink = `https://api.whatsapp.com/send?text=${encodedTitle}%20${encodedUrl}`;
                    break;
                case 'telegram':
                    shareLink = `https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`;
                    break;
                case 'reddit':
                    shareLink = `https://reddit.com/submit?url=${encodedUrl}&title=${encodedTitle}`;
                    break;
                case 'email':
                    shareLink = `mailto:?subject=${encodedTitle}&body=Check%20out%20this%20video:%20${encodedUrl}`;
                    break;
            }

            if (shareLink) {
                if (platform === 'email') {
                    window.location.href = shareLink;
                } else {
                    window.open(shareLink, '_blank', 'width=600,height=400,scrollbars=yes');
                }
            }
        },

        formatTimestamp(seconds) {
            const hrs = Math.floor(seconds / 3600);
            const mins = Math.floor((seconds % 3600) / 60);
            const secs = Math.floor(seconds % 60);
            
            if (hrs > 0) {
                return `${hrs}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
    };
}
</script>
