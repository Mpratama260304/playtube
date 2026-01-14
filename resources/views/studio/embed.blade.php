<x-main-layout>
    <x-slot name="title">Add Embed Video - {{ config('app.name') }}</x-slot>

    <div class="max-w-3xl mx-auto" x-data="embedForm()">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-white">Add Video from URL</h1>
            <p class="text-gray-400 mt-1">Embed videos from YouTube, Dailymotion, Google Drive, Vimeo, and more</p>
        </div>

        @if(!$isCreator)
        <!-- Creator Access Required Modal (same as upload page) -->
        <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50">
            <div class="bg-gray-900 rounded-xl p-8 max-w-lg w-full mx-4">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 bg-yellow-500/20 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-white">Creator Access Required</h2>
                    <p class="text-gray-400 mt-2">You need creator permission to add videos on PlayTube.</p>
                </div>
                <div class="flex justify-center">
                    <a href="{{ route('studio.upload') }}" class="py-3 px-6 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg transition-colors">
                        Request Creator Access
                    </a>
                </div>
            </div>
        </div>
        @else

        <!-- Supported Platforms -->
        <div class="bg-gray-800/50 rounded-xl p-4 mb-6">
            <p class="text-sm text-gray-400 mb-2">Supported platforms:</p>
            <div class="flex flex-wrap gap-2">
                @foreach($supportedPlatforms as $key => $name)
                <span class="px-2 py-1 bg-gray-700/50 rounded text-xs text-gray-300">{{ $name }}</span>
                @endforeach
            </div>
        </div>

        <!-- Main Form -->
        <form @submit.prevent="submitForm()" class="space-y-6">
            <!-- URL Input -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Video URL *</label>
                <div class="flex gap-2">
                    <input
                        type="url"
                        x-model="formData.url"
                        @input="debounceParseUrl()"
                        class="flex-1 px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                        placeholder="https://www.youtube.com/watch?v=... or https://drive.google.com/file/d/..."
                        required
                    >
                    <button
                        type="button"
                        @click="parseUrl()"
                        class="px-4 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
                        :disabled="parsing || !formData.url"
                    >
                        <span x-show="!parsing">Check</span>
                        <svg x-show="parsing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">Paste a video URL from any supported platform</p>
            </div>

            <!-- Preview Section (shows after URL is parsed) -->
            <div x-show="parsedData" x-cloak class="bg-gray-800 rounded-xl p-4">
                <div class="flex items-start gap-4">
                    <!-- Thumbnail Preview -->
                    <div class="w-40 h-24 bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
                        <template x-if="parsedData?.thumbnail_url">
                            <img :src="parsedData.thumbnail_url" class="w-full h-full object-cover" alt="Video thumbnail">
                        </template>
                        <template x-if="!parsedData?.thumbnail_url">
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </template>
                    </div>
                    <!-- Info -->
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 bg-brand-600/30 text-brand-400 text-xs rounded" x-text="parsedData?.platform_name"></span>
                            <span class="text-green-500 text-sm">✓ Valid URL</span>
                        </div>
                        <p class="text-gray-400 text-sm">Video ID: <span class="text-white font-mono" x-text="parsedData?.video_id"></span></p>
                    </div>
                </div>
            </div>

            <!-- Parse Error -->
            <div x-show="parseError" x-cloak class="p-3 bg-red-500/20 border border-red-500/50 rounded-lg text-red-500 text-sm" x-text="parseError"></div>

            <!-- Video Details (only show after successful parse) -->
            <div x-show="parsedData" x-cloak class="space-y-6">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Title *</label>
                    <input
                        type="text"
                        x-model="formData.title"
                        class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                        placeholder="Enter video title"
                        maxlength="255"
                        required
                    >
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea
                        x-model="formData.description"
                        rows="4"
                        class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                        placeholder="Enter video description (optional)"
                        maxlength="5000"
                    ></textarea>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Category</label>
                    <select
                        x-model="formData.category_id"
                        class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                    >
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tags -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Tags</label>
                    <input
                        type="text"
                        x-model="formData.tags"
                        class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                        placeholder="gaming, tutorial, review (comma separated)"
                        maxlength="500"
                    >
                </div>

                <!-- Visibility -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Visibility</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="visibility" value="public" x-model="formData.visibility" class="sr-only peer">
                            <div class="p-3 bg-gray-800 border border-gray-700 rounded-lg text-center peer-checked:border-brand-500 peer-checked:bg-brand-500/10 transition-colors">
                                <svg class="w-6 h-6 mx-auto mb-1 text-gray-400 peer-checked:text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm text-gray-300">Public</span>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="visibility" value="unlisted" x-model="formData.visibility" class="sr-only peer">
                            <div class="p-3 bg-gray-800 border border-gray-700 rounded-lg text-center peer-checked:border-brand-500 peer-checked:bg-brand-500/10 transition-colors">
                                <svg class="w-6 h-6 mx-auto mb-1 text-gray-400 peer-checked:text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                <span class="text-sm text-gray-300">Unlisted</span>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="visibility" value="private" x-model="formData.visibility" class="sr-only peer">
                            <div class="p-3 bg-gray-800 border border-gray-700 rounded-lg text-center peer-checked:border-brand-500 peer-checked:bg-brand-500/10 transition-colors">
                                <svg class="w-6 h-6 mx-auto mb-1 text-gray-400 peer-checked:text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <span class="text-sm text-gray-300">Private</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Short Video Toggle -->
                <div>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" x-model="formData.is_short" class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-300">This is a Short (< 60 seconds)</span>
                    </label>
                </div>

                <!-- Custom Thumbnail Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Custom Thumbnail (Optional)</label>
                    <div class="flex items-center space-x-4">
                        <div class="w-40 aspect-video bg-gray-800 rounded-lg overflow-hidden">
                            <template x-if="thumbnailPreview">
                                <img :src="thumbnailPreview" alt="Custom thumbnail" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!thumbnailPreview && parsedData?.thumbnail_url">
                                <img :src="parsedData.thumbnail_url" alt="Auto thumbnail" class="w-full h-full object-cover opacity-60">
                            </template>
                            <template x-if="!thumbnailPreview && !parsedData?.thumbnail_url">
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </template>
                        </div>
                        <div>
                            <label class="px-4 py-2 bg-gray-700 text-white rounded-lg cursor-pointer hover:bg-gray-600 transition-colors inline-block">
                                <span x-text="thumbnailFile ? 'Change' : 'Upload Thumbnail'"></span>
                                <input type="file" 
                                       accept="image/jpeg,image/png,image/webp"
                                       class="hidden"
                                       @change="handleThumbnailSelect($event)">
                            </label>
                            <button type="button" 
                                    x-show="thumbnailFile" 
                                    @click="clearThumbnail()"
                                    class="ml-2 px-3 py-2 text-red-400 hover:text-red-300 text-sm">
                                Remove
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Upload a custom thumbnail to avoid black first-frame on embeds. JPG, PNG, WebP (max 50MB)</p>
                </div>

                <!-- Submit Error -->
                <div x-show="submitError" x-cloak class="p-3 bg-red-500/20 border border-red-500/50 rounded-lg text-red-500 text-sm" x-text="submitError"></div>

                <!-- Submit Button -->
                <div class="flex gap-4">
                    <a href="{{ route('studio.dashboard') }}" class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="flex-1 px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="submitting || !parsedData || !formData.title"
                    >
                        <span x-show="!submitting">Add Video</span>
                        <span x-show="submitting" class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Adding Video...
                        </span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Alternative: Upload your own video -->
        <div class="mt-8 pt-8 border-t border-gray-800">
            <p class="text-gray-400 text-center">
                Want to upload your own video file instead? 
                <a href="{{ route('studio.upload') }}" class="text-brand-500 hover:text-brand-400">Upload Video</a>
            </p>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function embedForm() {
            return {
                formData: {
                    url: '',
                    title: '',
                    description: '',
                    category_id: '',
                    tags: '',
                    visibility: 'public',
                    is_short: false,
                },
                thumbnailFile: null,
                thumbnailPreview: null,
                parsedData: null,
                parsing: false,
                parseError: '',
                submitting: false,
                submitError: '',
                parseTimeout: null,

                handleThumbnailSelect(event) {
                    const file = event.target.files[0];
                    if (file) {
                        // Validate file type
                        const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
                        if (!validTypes.includes(file.type)) {
                            this.submitError = 'Invalid file type. Please use JPG, PNG, or WebP.';
                            return;
                        }
                        // Validate file size (50MB)
                        if (file.size > 50 * 1024 * 1024) {
                            this.submitError = 'File too large. Maximum size is 50MB.';
                            return;
                        }
                        this.thumbnailFile = file;
                        this.thumbnailPreview = URL.createObjectURL(file);
                        this.submitError = '';
                    }
                },

                clearThumbnail() {
                    this.thumbnailFile = null;
                    this.thumbnailPreview = null;
                },

                debounceParseUrl() {
                    clearTimeout(this.parseTimeout);
                    this.parseTimeout = setTimeout(() => {
                        if (this.formData.url.length > 10) {
                            this.parseUrl();
                        }
                    }, 800);
                },

                async parseUrl() {
                    if (!this.formData.url) return;
                    
                    this.parsing = true;
                    this.parseError = '';
                    this.parsedData = null;

                    try {
                        const response = await fetch('{{ route("studio.embed.parse") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ url: this.formData.url })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.parsedData = data.data;
                            // Auto-fill title if empty
                            if (!this.formData.title && this.parsedData.platform_name) {
                                this.formData.title = 'Video from ' + this.parsedData.platform_name;
                            }
                        } else {
                            this.parseError = data.message || 'Failed to parse URL';
                        }
                    } catch (error) {
                        console.error('Parse error:', error);
                        this.parseError = 'Failed to check URL. Please try again.';
                    } finally {
                        this.parsing = false;
                    }
                },

                async submitForm() {
                    if (!this.parsedData || !this.formData.title) return;

                    this.submitting = true;
                    this.submitError = '';

                    try {
                        // Use FormData to support file upload
                        const formData = new FormData();
                        formData.append('url', this.formData.url);
                        formData.append('title', this.formData.title);
                        formData.append('description', this.formData.description || '');
                        formData.append('category_id', this.formData.category_id || '');
                        formData.append('tags', this.formData.tags || '');
                        formData.append('visibility', this.formData.visibility);
                        formData.append('is_short', this.formData.is_short ? '1' : '0');
                        
                        // Add thumbnail file if selected
                        if (this.thumbnailFile) {
                            formData.append('thumbnail', this.thumbnailFile);
                        }

                        const response = await fetch('{{ route("studio.embed.store") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Redirect to edit page
                            window.location.href = data.video.edit_url;
                        } else {
                            this.submitError = data.message || 'Failed to add video';
                        }
                    } catch (error) {
                        console.error('Submit error:', error);
                        this.submitError = 'Failed to add video. Please try again.';
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
    @endpush
</x-main-layout>
