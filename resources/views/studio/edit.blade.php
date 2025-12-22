<x-main-layout>
    <x-slot name="title">Edit Video - {{ config('app.name') }}</x-slot>

    <div class="max-w-3xl mx-auto" 
         x-data="{ 
             saving: false,
             retrying: false,
             videoStatus: '{{ $video->status }}',
             processingState: '{{ $video->processing_state ?? 'ready' }}',
             processingError: '{{ $video->processing_error }}',
             thumbnailUrl: '{{ $video->thumbnail_url }}',
             hlsUrl: '{{ $video->hls_url }}',
             durationFormatted: '{{ $video->duration_formatted ?? '--:--' }}',
             pollingInterval: null,
             
             startPolling() {
                 // Poll for optimization state updates (non-blocking, informational)
                 if (this.processingState === 'pending' || this.processingState === 'processing') {
                     this.pollingInterval = setInterval(() => this.checkStatus(), 10000);
                 }
             },
             
             stopPolling() {
                 if (this.pollingInterval) {
                     clearInterval(this.pollingInterval);
                     this.pollingInterval = null;
                 }
             },
             
             async checkStatus() {
                 try {
                     const response = await fetch('{{ route('studio.video-status', $video) }}');
                     const data = await response.json();
                     
                     this.videoStatus = data.status;
                     this.processingState = data.processing_state || 'ready';
                     this.processingError = data.processing_error || '';
                     this.thumbnailUrl = data.thumbnail_url || '';
                     this.hlsUrl = data.hls_url || '';
                     this.durationFormatted = data.duration_formatted || '--:--';
                     
                     // Stop polling when optimization is complete
                     if (this.processingState === 'ready' || this.processingState === 'failed') {
                         this.stopPolling();
                     }
                 } catch (e) {
                     console.error('Failed to check status', e);
                 }
             },
             
             async retryProcessing() {
                 if (this.retrying) return;
                 this.retrying = true;
                 
                 try {
                     const response = await fetch('{{ route('studio.retry', $video) }}', {
                         method: 'POST',
                         headers: {
                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                             'Accept': 'application/json'
                         }
                     });
                     
                     const data = await response.json();
                     
                     if (data.success) {
                         this.processingState = 'pending';
                         this.processingError = '';
                         this.startPolling();
                     } else {
                         alert(data.message || 'Failed to retry processing');
                     }
                 } catch (e) {
                     console.error('Failed to retry', e);
                     alert('Failed to retry processing');
                 } finally {
                     this.retrying = false;
                 }
             },
             
             init() {
                 this.startPolling();
             }
         }"
         x-init="init()">
        <!-- Saving Overlay -->
        <div x-show="saving" 
             x-cloak
             class="fixed inset-0 bg-black/80 flex items-center justify-center z-50">
            <div class="bg-gray-900 rounded-xl p-8 max-w-md w-full mx-4 text-center">
                <svg class="w-16 h-16 mx-auto text-red-500 animate-spin mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <h3 class="text-xl font-bold text-white mb-2">Saving Changes...</h3>
                <p class="text-gray-400">Please wait while we update your video.</p>
            </div>
        </div>

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-white">Edit Video</h1>
                <p class="text-gray-400 text-sm mt-1">
                    Created {{ $video->created_at->format('M d, Y') }} • 
                    <span class="px-2 py-0.5 text-xs rounded-full"
                          :class="{
                              'bg-green-900 text-green-300': videoStatus === 'published',
                              'bg-yellow-900 text-yellow-300': videoStatus === 'processing',
                              'bg-red-900 text-red-300': videoStatus === 'failed',
                              'bg-gray-700 text-gray-300': !['published', 'processing', 'failed'].includes(videoStatus)
                          }"
                          x-text="videoStatus.charAt(0).toUpperCase() + videoStatus.slice(1)">
                    </span>
                </p>
            </div>
            <a href="{{ route('studio.videos') }}" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                ← Back to Videos
            </a>
        </div>

        <!-- Background Optimization Banner (non-blocking, informational only) -->
        <template x-if="processingState === 'pending' || processingState === 'processing'">
            <div class="mb-6 p-4 bg-blue-900/20 border border-blue-600 rounded-lg flex items-center space-x-3">
                <svg class="w-5 h-5 text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <div>
                    <span class="text-blue-300 font-medium">Optimizing video in background</span>
                    <p class="text-blue-400/70 text-sm">Your video is published and watchable. Background optimization improves streaming quality.</p>
                </div>
            </div>
        </template>
        
        <template x-if="videoStatus === 'failed'">
            <div class="mb-6 p-4 bg-red-500/20 border border-red-500 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-red-500 font-medium">Video processing failed</span>
                    </div>
                    <button @click="retryProcessing()" 
                            :disabled="retrying"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2">
                        <svg x-show="!retrying" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg x-show="retrying" class="w-4 h-4 animate-spin" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="retrying ? 'Retrying...' : 'Retry Processing'"></span>
                    </button>
                </div>
                <template x-if="processingError">
                    <p class="text-red-400 text-sm ml-8" x-text="processingError"></p>
                </template>
                <p class="text-red-400/70 text-sm ml-8 mt-2">Click "Retry Processing" to try again, or re-upload the video if the issue persists.</p>
            </div>
        </template>

        <!-- Success Message -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-500/20 border border-green-500 rounded-lg flex items-center space-x-3">
                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-green-500">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Error Messages -->
        @if($errors->any())
            <div class="mb-6 p-4 bg-red-500/20 border border-red-500 rounded-lg">
                <div class="flex items-center space-x-3 mb-2">
                    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-500 font-medium">Please fix the following errors:</span>
                </div>
                <ul class="list-disc list-inside text-red-400 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Video Preview -->
        <div class="mb-8 bg-gray-800 rounded-xl p-6">
            <h2 class="text-lg font-medium text-white mb-4">Video Preview</h2>
            <div class="flex items-start space-x-4">
                <div class="w-48 aspect-video bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
                    @if($video->thumbnail_path)
                        <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white font-medium truncate">{{ $video->title }}</p>
                    <p class="text-gray-400 text-sm mt-1">
                        {{ number_format($video->views_count ?? 0) }} views • 
                        {{ $video->duration_formatted ?? '--:--' }}
                    </p>
                    <p class="text-gray-500 text-xs mt-2 font-mono">slug: {{ $video->slug }}</p>
                    @if($video->status === 'published')
                        <a href="{{ route('video.watch', $video) }}" target="_blank" class="inline-flex items-center text-blue-400 hover:text-blue-300 text-sm mt-2">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            View on site
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <form action="{{ route('studio.update', $video) }}" 
              method="POST" 
              enctype="multipart/form-data" 
              class="space-y-6"
              @submit="saving = true">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-300 mb-2">Title *</label>
                <input type="text" 
                       name="title" 
                       id="title" 
                       value="{{ old('title', $video->title) }}"
                       required
                       maxlength="255"
                       class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-gray-600 focus:ring-1 focus:ring-gray-600"
                       placeholder="Enter video title">
                @error('title')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                <textarea name="description" 
                          id="description" 
                          rows="5"
                          class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-gray-600 focus:ring-1 focus:ring-gray-600 resize-none"
                          placeholder="Tell viewers about your video">{{ old('description', $video->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Thumbnail -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Thumbnail</label>
                <div class="flex items-center space-x-4">
                    <div class="w-40 aspect-video bg-gray-800 rounded-lg overflow-hidden" x-data="{ preview: null }">
                        <img x-show="preview" :src="preview" alt="New thumbnail" class="w-full h-full object-cover">
                        <div x-show="!preview">
                            @if($video->has_thumbnail)
                                <img src="{{ $video->thumbnail_url }}" alt="Current thumbnail" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <input type="file" 
                               name="thumbnail" 
                               accept="image/*"
                               class="hidden"
                               id="thumbnail-input"
                               @change="preview = URL.createObjectURL($event.target.files[0])">
                    </div>
                    <label for="thumbnail-input" class="px-4 py-2 bg-gray-700 text-white rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                        Change Thumbnail
                    </label>
                </div>
                <p class="text-sm text-gray-500 mt-2">JPG, PNG (1280x720 recommended)</p>
                @error('thumbnail')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category -->
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-300 mb-2">Category</label>
                <select name="category_id" 
                        id="category_id"
                        class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-gray-600 focus:ring-1 focus:ring-gray-600">
                    <option value="">Select a category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $video->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tags -->
            <div>
                <label for="tags" class="block text-sm font-medium text-gray-300 mb-2">Tags</label>
                <input type="text" 
                       name="tags" 
                       id="tags" 
                       value="{{ old('tags', $tags) }}"
                       class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-gray-600 focus:ring-1 focus:ring-gray-600"
                       placeholder="gaming, tutorial, vlog (comma separated)">
                <p class="text-sm text-gray-500 mt-2">Separate tags with commas</p>
                @error('tags')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Visibility -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Visibility</label>
                <div class="space-y-3">
                    <label class="flex items-center p-4 bg-gray-800 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-750">
                        <input type="radio" name="visibility" value="public" {{ old('visibility', $video->visibility) === 'public' ? 'checked' : '' }} class="text-red-600 focus:ring-red-500">
                        <div class="ml-3">
                            <p class="font-medium text-white">Public</p>
                            <p class="text-sm text-gray-400">Everyone can watch your video</p>
                        </div>
                    </label>
                    <label class="flex items-center p-4 bg-gray-800 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-750">
                        <input type="radio" name="visibility" value="unlisted" {{ old('visibility', $video->visibility) === 'unlisted' ? 'checked' : '' }} class="text-red-600 focus:ring-red-500">
                        <div class="ml-3">
                            <p class="font-medium text-white">Unlisted</p>
                            <p class="text-sm text-gray-400">Anyone with the link can watch</p>
                        </div>
                    </label>
                    <label class="flex items-center p-4 bg-gray-800 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-750">
                        <input type="radio" name="visibility" value="private" {{ old('visibility', $video->visibility) === 'private' ? 'checked' : '' }} class="text-red-600 focus:ring-red-500">
                        <div class="ml-3">
                            <p class="font-medium text-white">Private</p>
                            <p class="text-sm text-gray-400">Only you can watch</p>
                        </div>
                    </label>
                </div>
                @error('visibility')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Video Type (Short) -->
            <div>
                <label class="flex items-center space-x-3">
                    <input type="checkbox" name="is_short" value="1" {{ old('is_short', $video->is_short) ? 'checked' : '' }} class="rounded bg-gray-800 border-gray-700 text-red-600 focus:ring-red-500">
                    <div>
                        <p class="font-medium text-white">This is a Short</p>
                        <p class="text-sm text-gray-400">Videos under 60 seconds, vertical format</p>
                    </div>
                </label>
            </div>

            <!-- Submit -->
            <div class="flex justify-end space-x-4 pt-4 border-t border-gray-700">
                <a href="{{ route('studio.videos') }}" class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        :disabled="saving"
                        :class="{'opacity-50 cursor-not-allowed': saving}"
                        class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <span x-show="!saving">Save Changes</span>
                    <span x-show="saving" class="flex items-center space-x-2">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Saving...</span>
                    </span>
                </button>
            </div>
        </form>

        <!-- Danger Zone -->
        <div class="mt-12 p-6 bg-red-900/20 border border-red-900 rounded-xl">
            <h2 class="text-lg font-medium text-red-400 mb-4">Danger Zone</h2>
            <p class="text-gray-400 text-sm mb-4">Once you delete a video, there is no going back. Please be certain.</p>
            <form action="{{ route('studio.destroy', $video) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this video? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Delete Video
                </button>
            </form>
        </div>
    </div>
</x-main-layout>
