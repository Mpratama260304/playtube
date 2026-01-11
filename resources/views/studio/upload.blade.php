<x-main-layout>
    <x-slot name="title">Upload Video - {{ config('app.name') }}</x-slot>

    <div class="max-w-3xl mx-auto" x-data="uploadForm()">
        <h1 class="text-2xl font-bold text-white mb-8">Upload Video</h1>

        @if(!$isCreator)
        <!-- Creator Access Required Modal -->
        <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50" x-data="creatorRequest()">
            <div class="bg-gray-900 rounded-xl p-8 max-w-lg w-full mx-4">
                <!-- Header -->
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 bg-yellow-500/20 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-white">Creator Access Required</h2>
                    <p class="text-gray-400 mt-2">You need creator permission to upload videos on PlayTube.</p>
                </div>

                @if($hasPendingRequest)
                    <!-- Pending Request Status -->
                    <div class="bg-yellow-500/10 border border-yellow-500/50 rounded-lg p-4 mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-yellow-500 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-yellow-500 font-medium">Request Pending</p>
                                <p class="text-gray-400 text-sm">Your creator request is being reviewed by an administrator.</p>
                                @if($latestRequest)
                                    <p class="text-gray-500 text-xs mt-1">Submitted {{ $latestRequest->created_at->diffForHumans() }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <a href="{{ route('studio.dashboard') }}" class="flex-1 py-3 px-4 bg-gray-700 hover:bg-gray-600 text-white font-medium rounded-lg text-center transition-colors">
                            Back to Studio
                        </a>
                        <button @click="cancelRequest()" 
                                class="flex-1 py-3 px-4 bg-red-600/20 hover:bg-red-600/30 text-red-500 font-medium rounded-lg transition-colors"
                                :disabled="loading">
                            <span x-show="!loading">Cancel Request</span>
                            <span x-show="loading" class="flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>

                @elseif($latestRequest && $latestRequest->status === 'rejected')
                    <!-- Rejected Status -->
                    <div class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 mb-6">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-red-500 font-medium">Previous Request Rejected</p>
                                @if($latestRequest->admin_notes)
                                    <p class="text-gray-400 text-sm mt-1">Reason: {{ $latestRequest->admin_notes }}</p>
                                @endif
                                <p class="text-gray-500 text-xs mt-1">Rejected {{ $latestRequest->reviewed_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Allow re-request -->
                    <div x-show="!showForm">
                        <p class="text-gray-400 text-sm text-center mb-4">You can submit a new request with more information.</p>
                        <div class="flex space-x-3">
                            <a href="{{ route('studio.dashboard') }}" class="flex-1 py-3 px-4 bg-gray-700 hover:bg-gray-600 text-white font-medium rounded-lg text-center transition-colors">
                                Back to Studio
                            </a>
                            <button @click="showForm = true" class="flex-1 py-3 px-4 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg transition-colors">
                                Request Again
                            </button>
                        </div>
                    </div>

                    <!-- Request Form (for re-request) -->
                    <form x-show="showForm" @submit.prevent="submitRequest()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Why do you want to become a creator?</label>
                            <textarea x-model="reason" 
                                      rows="4" 
                                      class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                                      placeholder="Tell us about the type of content you want to create, your experience, and why you'd be a great addition to PlayTube..."
                                      required
                                      minlength="20"
                                      maxlength="1000"></textarea>
                            <p class="text-gray-500 text-xs mt-1" x-text="reason.length + '/1000 characters (minimum 20)'"></p>
                        </div>

                        <div x-show="error" class="p-3 bg-red-500/20 border border-red-500/50 rounded-lg text-red-500 text-sm" x-text="error"></div>

                        <div class="flex space-x-3">
                            <button type="button" @click="showForm = false" class="flex-1 py-3 px-4 bg-gray-700 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 py-3 px-4 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                                    :disabled="loading || reason.length < 20">
                                <span x-show="!loading">Submit Request</span>
                                <span x-show="loading" class="flex items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Submitting...
                                </span>
                            </button>
                        </div>
                    </form>

                @else
                    <!-- New Request Form -->
                    <form @submit.prevent="submitRequest()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Why do you want to become a creator?</label>
                            <textarea x-model="reason" 
                                      rows="4" 
                                      class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                                      placeholder="Tell us about the type of content you want to create, your experience, and why you'd be a great addition to PlayTube..."
                                      required
                                      minlength="20"
                                      maxlength="1000"></textarea>
                            <p class="text-gray-500 text-xs mt-1" x-text="reason.length + '/1000 characters (minimum 20)'"></p>
                        </div>

                        <div x-show="error" class="p-3 bg-red-500/20 border border-red-500/50 rounded-lg text-red-500 text-sm" x-text="error"></div>
                        <div x-show="success" class="p-3 bg-green-500/20 border border-green-500/50 rounded-lg text-green-500 text-sm" x-text="success"></div>

                        <div class="flex space-x-3">
                            <a href="{{ route('studio.dashboard') }}" class="flex-1 py-3 px-4 bg-gray-700 hover:bg-gray-600 text-white font-medium rounded-lg text-center transition-colors">
                                Back to Studio
                            </a>
                            <button type="submit" 
                                    class="flex-1 py-3 px-4 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                                    :disabled="loading || reason.length < 20">
                                <span x-show="!loading">Submit Request</span>
                                <span x-show="loading" class="flex items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Submitting...
                                </span>
                            </button>
                        </div>
                    </form>
                @endif

                <!-- Info -->
                <div class="mt-6 pt-6 border-t border-gray-800">
                    <p class="text-gray-500 text-xs text-center">
                        As a creator, you'll be able to upload videos, create playlists, and build your audience on PlayTube.
                    </p>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
            function creatorRequest() {
                return {
                    reason: '',
                    loading: false,
                    error: '',
                    success: '',
                    showForm: false,

                    async submitRequest() {
                        this.loading = true;
                        this.error = '';
                        this.success = '';

                        try {
                            const response = await fetch('{{ route('creator.request') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ reason: this.reason })
                            });

                            const data = await response.json();

                            if (data.success) {
                                this.success = data.message;
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                this.error = data.message || 'Failed to submit request';
                            }
                        } catch (e) {
                            this.error = 'Network error. Please try again.';
                        } finally {
                            this.loading = false;
                        }
                    },

                    async cancelRequest() {
                        if (!confirm('Are you sure you want to cancel your creator request?')) return;
                        
                        this.loading = true;
                        this.error = '';

                        try {
                            const response = await fetch('{{ route('creator.cancel') }}', {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            });

                            const data = await response.json();

                            if (data.success) {
                                window.location.reload();
                            } else {
                                this.error = data.message || 'Failed to cancel request';
                            }
                        } catch (e) {
                            this.error = 'Network error. Please try again.';
                        } finally {
                            this.loading = false;
                        }
                    }
                }
            }
        </script>
        @endpush
        @endif

        <!-- Upload Progress Overlay -->
        <div x-show="uploading" 
             x-cloak
             class="fixed inset-0 bg-black/80 flex items-center justify-center z-50">
            <div class="bg-gray-900 rounded-xl p-8 max-w-md w-full mx-4 text-center">
                <div class="mb-6">
                    <svg class="w-16 h-16 mx-auto text-red-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Uploading Video...</h3>
                <p class="text-gray-400 mb-6" x-text="fileName">video.mp4</p>
                
                <!-- Progress Bar -->
                <div class="w-full bg-gray-700 rounded-full h-3 mb-4">
                    <div class="bg-gradient-to-r from-red-600 to-red-500 h-3 rounded-full transition-all duration-300"
                         :style="'width: ' + progress + '%'"></div>
                </div>
                
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400" x-text="progress + '%'">0%</span>
                    <span class="text-gray-400" x-text="uploadSpeed">-- MB/s</span>
                    <span class="text-gray-400" x-text="remainingTime">Calculating...</span>
                </div>
                
                <button @click="cancelUpload()" 
                        class="mt-6 px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    Cancel
                </button>
            </div>
        </div>

        <!-- Success Message -->
        <div x-show="uploadSuccess" 
             x-cloak
             x-transition
             class="mb-6 p-4 bg-green-500/20 border border-green-500 rounded-lg flex items-center space-x-3">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-green-500" x-text="successMessage">Video uploaded successfully!</span>
        </div>

        <!-- Error Message -->
        <div x-show="uploadError" 
             x-cloak
             x-transition
             class="mb-6 p-4 bg-red-500/20 border border-red-500 rounded-lg flex items-center space-x-3">
            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span class="text-red-500" x-text="errorMessage">Upload failed</span>
        </div>

        @if (session('success'))
        <div class="mb-6 p-4 bg-green-500/20 border border-green-500 rounded-lg flex items-center space-x-3">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-green-500">{{ session('success') }}</span>
        </div>
        @endif

        <form @submit.prevent="submitForm" class="space-y-6">
            @csrf

            <!-- Video File -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Video File *</label>
                <div class="relative border-2 border-dashed border-gray-700 rounded-xl p-8 text-center hover:border-gray-600 transition-colors" 
                     :class="{ 'border-blue-500 bg-blue-500/10': dragover, 'border-green-500 bg-green-500/10': fileName }"
                     @dragover.prevent="dragover = true"
                     @dragleave.prevent="dragover = false"
                     @drop.prevent="handleDrop($event)">
                    <input type="file" 
                           id="video-input"
                           name="video" 
                           accept="video/*" 
                           required
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                           @change="handleFileSelect($event)">
                    <svg class="w-12 h-12 mx-auto mb-4" :class="fileName ? 'text-green-500' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-gray-400 mb-2" x-show="!fileName">Drag and drop your video here or click to browse</p>
                    <p class="text-white font-medium" x-show="fileName" x-text="fileName + ' (' + fileSize + ')'"></p>
                    <p class="text-sm text-gray-500">MP4, WebM, MOV (max 500MB)</p>
                </div>
                @error('video')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-300 mb-2">Title *</label>
                <input type="text" 
                       name="title" 
                       id="title" 
                       x-model="formData.title"
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
                          x-model="formData.description"
                          rows="5"
                          class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-gray-600 focus:ring-1 focus:ring-gray-600 resize-none"
                          placeholder="Tell viewers about your video"></textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Thumbnail -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Thumbnail</label>
                <div class="flex items-center space-x-4">
                    <div class="w-40 aspect-video bg-gray-800 rounded-lg overflow-hidden">
                        <img x-show="thumbnailPreview" :src="thumbnailPreview" alt="Thumbnail preview" class="w-full h-full object-cover">
                        <div x-show="!thumbnailPreview" class="w-full h-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <label class="px-4 py-2 bg-gray-700 text-white rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                        Upload Thumbnail
                        <input type="file" 
                               name="thumbnail" 
                               accept="image/*"
                               class="hidden"
                               @change="handleThumbnailSelect($event)">
                    </label>
                </div>
                <p class="text-sm text-gray-500 mt-2">JPG, PNG (1280x720 recommended). If not provided, a thumbnail will be auto-generated.</p>
                @error('thumbnail')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category -->
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-300 mb-2">Category</label>
                <select name="category_id" 
                        id="category_id"
                        x-model="formData.category_id"
                        class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-gray-600 focus:ring-1 focus:ring-gray-600">
                    <option value="">Select a category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
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
                       x-model="formData.tags"
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
                        <input type="radio" name="visibility" value="public" x-model="formData.visibility" class="text-red-600 focus:ring-red-500">
                        <div class="ml-3">
                            <p class="font-medium text-white">Public</p>
                            <p class="text-sm text-gray-400">Everyone can watch your video</p>
                        </div>
                    </label>
                    <label class="flex items-center p-4 bg-gray-800 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-750">
                        <input type="radio" name="visibility" value="unlisted" x-model="formData.visibility" class="text-red-600 focus:ring-red-500">
                        <div class="ml-3">
                            <p class="font-medium text-white">Unlisted</p>
                            <p class="text-sm text-gray-400">Anyone with the link can watch</p>
                        </div>
                    </label>
                    <label class="flex items-center p-4 bg-gray-800 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-750">
                        <input type="radio" name="visibility" value="private" x-model="formData.visibility" class="text-red-600 focus:ring-red-500">
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
                    <input type="checkbox" name="is_short" value="1" x-model="formData.is_short" class="rounded bg-gray-800 border-gray-700 text-red-600 focus:ring-red-500">
                    <div>
                        <p class="font-medium text-white">This is a Short</p>
                        <p class="text-sm text-gray-400">Videos under 60 seconds, vertical format</p>
                    </div>
                </label>
            </div>

            <!-- Submit -->
            <div class="flex justify-end space-x-4 pt-4">
                <a href="{{ route('studio.dashboard') }}" class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        :disabled="uploading || !videoFile"
                        :class="{'opacity-50 cursor-not-allowed': uploading || !videoFile}"
                        class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <span x-show="!uploading">Upload Video</span>
                    <span x-show="uploading" class="flex items-center space-x-2">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Uploading...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function uploadForm() {
            return {
                dragover: false,
                uploading: false,
                progress: 0,
                uploadSpeed: '-- MB/s',
                remainingTime: 'Calculating...',
                fileName: '',
                fileSize: '',
                videoFile: null,
                thumbnailFile: null,
                thumbnailPreview: null,
                uploadSuccess: false,
                uploadError: false,
                successMessage: '',
                errorMessage: '',
                xhr: null,
                startTime: null,
                uploadId: null,
                abortController: null,
                
                // Chunked upload settings
                CHUNK_SIZE: 5 * 1024 * 1024, // 5MB chunks
                USE_CHUNKED: true, // Enable chunked upload by default
                
                formData: {
                    title: '{{ old('title') }}',
                    description: '{{ old('description') }}',
                    category_id: '{{ old('category_id') }}',
                    tags: '{{ old('tags') }}',
                    visibility: '{{ old('visibility', 'public') }}',
                    is_short: {{ old('is_short') ? 'true' : 'false' }}
                },

                handleDrop(event) {
                    this.dragover = false;
                    const file = event.dataTransfer.files[0];
                    if (file && file.type.startsWith('video/')) {
                        this.setVideoFile(file);
                    }
                },

                handleFileSelect(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.setVideoFile(file);
                    }
                },

                setVideoFile(file) {
                    this.videoFile = file;
                    this.fileName = file.name;
                    this.fileSize = this.formatFileSize(file.size);
                    this.uploadError = false;
                    this.uploadSuccess = false;
                    
                    // Auto-fill title if empty
                    if (!this.formData.title) {
                        this.formData.title = file.name.replace(/\.[^/.]+$/, '');
                    }
                },

                handleThumbnailSelect(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.thumbnailFile = file;
                        this.thumbnailPreview = URL.createObjectURL(file);
                    }
                },

                formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                },

                submitForm() {
                    if (!this.videoFile || this.uploading) return;
                    
                    // Use chunked upload for files > 50MB or when USE_CHUNKED is true
                    if (this.USE_CHUNKED || this.videoFile.size > 50 * 1024 * 1024) {
                        this.chunkedUpload();
                    } else {
                        this.directUpload();
                    }
                },
                
                async chunkedUpload() {
                    this.uploading = true;
                    this.progress = 0;
                    this.uploadError = false;
                    this.uploadSuccess = false;
                    this.startTime = Date.now();
                    this.abortController = new AbortController();

                    try {
                        // Step 1: Initialize upload session
                        const initResponse = await fetch('{{ route('studio.chunked.init') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                filename: this.videoFile.name,
                                filesize: this.videoFile.size,
                                mimetype: this.videoFile.type,
                                title: this.formData.title,
                                description: this.formData.description,
                                category_id: this.formData.category_id,
                                tags: this.formData.tags,
                                visibility: this.formData.visibility,
                                is_short: this.formData.is_short,
                            }),
                            signal: this.abortController.signal,
                        });

                        const initData = await initResponse.json();
                        
                        if (!initData.success) {
                            throw new Error(initData.message || 'Failed to initialize upload');
                        }

                        this.uploadId = initData.upload_id;
                        const chunkSize = initData.chunk_size || this.CHUNK_SIZE;
                        const totalChunks = Math.ceil(this.videoFile.size / chunkSize);
                        let uploadedBytes = 0;

                        // Step 2: Upload chunks
                        for (let i = 0; i < totalChunks; i++) {
                            if (this.abortController.signal.aborted) {
                                throw new Error('Upload cancelled');
                            }

                            const start = i * chunkSize;
                            const end = Math.min(start + chunkSize, this.videoFile.size);
                            const chunk = this.videoFile.slice(start, end);

                            const chunkFormData = new FormData();
                            chunkFormData.append('upload_id', this.uploadId);
                            chunkFormData.append('chunk_index', i);
                            chunkFormData.append('total_chunks', totalChunks);
                            chunkFormData.append('chunk', chunk, `chunk_${i}`);

                            const chunkResponse = await fetch('{{ route('studio.chunked.chunk') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: chunkFormData,
                                signal: this.abortController.signal,
                            });

                            const chunkData = await chunkResponse.json();
                            
                            if (!chunkData.success) {
                                throw new Error(chunkData.message || `Failed to upload chunk ${i}`);
                            }

                            uploadedBytes += (end - start);
                            this.progress = Math.round((uploadedBytes / this.videoFile.size) * 100);
                            
                            // Calculate speed and remaining time
                            const elapsed = (Date.now() - this.startTime) / 1000;
                            if (elapsed > 0) {
                                const bytesPerSecond = uploadedBytes / elapsed;
                                this.uploadSpeed = this.formatFileSize(bytesPerSecond) + '/s';
                                
                                const remaining = this.videoFile.size - uploadedBytes;
                                const secondsLeft = remaining / bytesPerSecond;
                                this.remainingTime = this.formatTime(secondsLeft);
                            }
                        }

                        // Step 3: Complete upload
                        const completeResponse = await fetch('{{ route('studio.chunked.complete') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                upload_id: this.uploadId,
                            }),
                            signal: this.abortController.signal,
                        });

                        const completeData = await completeResponse.json();
                        
                        if (!completeData.success) {
                            throw new Error(completeData.message || 'Failed to complete upload');
                        }

                        this.uploading = false;
                        this.uploadSuccess = true;
                        this.successMessage = completeData.message;
                        
                        // Redirect after short delay
                        setTimeout(() => {
                            window.location.href = completeData.redirect;
                        }, 1500);

                    } catch (error) {
                        this.uploading = false;
                        
                        if (error.name === 'AbortError' || error.message === 'Upload cancelled') {
                            // User cancelled - abort on server
                            if (this.uploadId) {
                                fetch('{{ route('studio.chunked.abort') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    },
                                    body: JSON.stringify({ upload_id: this.uploadId }),
                                });
                            }
                            this.progress = 0;
                        } else {
                            this.uploadError = true;
                            this.errorMessage = error.message || 'Upload failed. Please try again.';
                        }
                    }
                },
                
                directUpload() {
                    this.uploading = true;
                    this.progress = 0;
                    this.uploadError = false;
                    this.uploadSuccess = false;
                    this.startTime = Date.now();

                    const formData = new FormData();
                    formData.append('video', this.videoFile);
                    formData.append('title', this.formData.title);
                    formData.append('description', this.formData.description || '');
                    formData.append('category_id', this.formData.category_id || '');
                    formData.append('tags', this.formData.tags || '');
                    formData.append('visibility', this.formData.visibility);
                    if (this.formData.is_short) {
                        formData.append('is_short', '1');
                    }
                    if (this.thumbnailFile) {
                        formData.append('thumbnail', this.thumbnailFile);
                    }

                    this.xhr = new XMLHttpRequest();
                    
                    this.xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            this.progress = Math.round((e.loaded / e.total) * 100);
                            
                            // Calculate speed and remaining time
                            const elapsed = (Date.now() - this.startTime) / 1000;
                            if (elapsed > 0) {
                                const bytesPerSecond = e.loaded / elapsed;
                                this.uploadSpeed = this.formatFileSize(bytesPerSecond) + '/s';
                                
                                const remaining = e.total - e.loaded;
                                const secondsLeft = remaining / bytesPerSecond;
                                this.remainingTime = this.formatTime(secondsLeft);
                            }
                        }
                    });

                    this.xhr.addEventListener('load', () => {
                        this.uploading = false;
                        
                        try {
                            const response = JSON.parse(this.xhr.responseText);
                            
                            if (this.xhr.status === 200 && response.success) {
                                this.uploadSuccess = true;
                                this.successMessage = response.message;
                                
                                // Redirect after short delay
                                setTimeout(() => {
                                    window.location.href = response.redirect;
                                }, 1500);
                            } else {
                                this.uploadError = true;
                                this.errorMessage = response.message || 'Upload failed. Please try again.';
                            }
                        } catch (e) {
                            this.uploadError = true;
                            this.errorMessage = 'Upload failed. Server returned an invalid response.';
                        }
                    });

                    this.xhr.addEventListener('error', () => {
                        this.uploading = false;
                        this.uploadError = true;
                        this.errorMessage = 'Network error. Please check your connection and try again.';
                    });

                    this.xhr.addEventListener('abort', () => {
                        this.uploading = false;
                        this.progress = 0;
                    });

                    this.xhr.open('POST', '{{ route('studio.store.ajax') }}');
                    this.xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                    this.xhr.setRequestHeader('Accept', 'application/json');
                    this.xhr.send(formData);
                },

                cancelUpload() {
                    // Cancel chunked upload
                    if (this.abortController) {
                        this.abortController.abort();
                        this.abortController = null;
                    }
                    // Cancel direct upload
                    if (this.xhr) {
                        this.xhr.abort();
                        this.xhr = null;
                    }
                },

                formatTime(seconds) {
                    if (seconds < 60) {
                        return Math.round(seconds) + 's remaining';
                    } else if (seconds < 3600) {
                        return Math.round(seconds / 60) + 'm remaining';
                    } else {
                        return Math.round(seconds / 3600) + 'h remaining';
                    }
                }
            }
        }
    </script>
    @endpush
</x-main-layout>
