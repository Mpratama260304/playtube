<x-main-layout>
    <x-slot name="title">Playlists - {{ config('app.name') }}</x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-white">Your Playlists</h1>
            <button onclick="openCreatePlaylistModal()" class="flex items-center px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Playlist
            </button>
        </div>

        @if($playlists->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($playlists as $playlist)
                    <div class="group">
                        <a href="{{ route('playlist.show', $playlist->slug) }}" class="block">
                            <div class="relative aspect-video bg-gray-800 rounded-xl overflow-hidden mb-3">
                                @if($playlist->videos->first() && $playlist->videos->first()->thumbnail)
                                    <img src="{{ $playlist->videos->first()->thumbnail_url }}" alt="{{ $playlist->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                        </svg>
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
                                <div class="absolute bottom-2 right-2 bg-black/80 px-2 py-1 text-xs rounded">
                                    {{ $playlist->videos_count ?? $playlist->videos->count() }} videos
                                </div>
                            </div>
                        </a>
                        
                        <div class="flex justify-between items-start">
                            <div class="min-w-0">
                                <a href="{{ route('playlist.show', $playlist->slug) }}" class="font-medium text-white hover:text-gray-300 line-clamp-2">
                                    {{ $playlist->name }}
                                </a>
                                <p class="text-sm text-gray-400 mt-1">{{ ucfirst($playlist->visibility) }}</p>
                            </div>
                            
                            <!-- Menu -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="p-1 text-gray-400 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-1 w-40 bg-gray-800 rounded-lg shadow-lg border border-gray-700 py-1 z-10" style="display: none;">
                                    <button onclick="editPlaylist({{ $playlist->id }}, '{{ $playlist->name }}', '{{ $playlist->visibility }}')" class="flex items-center w-full px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </button>
                                    <form action="{{ route('playlists.destroy', $playlist) }}" method="POST" onsubmit="return confirm('Delete this playlist?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-400 hover:bg-gray-700">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($playlists->hasPages())
                <div class="mt-8">
                    {{ $playlists->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                <p class="text-gray-400 text-lg mb-4">No playlists yet</p>
                <button onclick="openCreatePlaylistModal()" class="px-4 py-2 bg-white text-gray-900 rounded-lg hover:bg-gray-200">
                    Create your first playlist
                </button>
            </div>
        @endif
    </div>

    <!-- Create/Edit Playlist Modal -->
    <div id="playlist-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closePlaylistModal()"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-gray-800 rounded-xl p-6 w-full max-w-md">
                <h2 id="modal-title" class="text-xl font-bold text-white mb-4">New Playlist</h2>
                <form id="playlist-form" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="POST">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Name</label>
                        <input type="text" name="name" id="playlist-name" required class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-gray-500">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Visibility</label>
                        <select name="visibility" id="playlist-visibility" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-gray-500">
                            <option value="public">Public</option>
                            <option value="unlisted">Unlisted</option>
                            <option value="private">Private</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closePlaylistModal()" class="px-4 py-2 text-gray-400 hover:text-white">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-white text-gray-900 rounded-lg hover:bg-gray-200">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function openCreatePlaylistModal() {
            document.getElementById('modal-title').textContent = 'New Playlist';
            document.getElementById('playlist-form').action = '{{ route('playlists.store') }}';
            document.getElementById('form-method').value = 'POST';
            document.getElementById('playlist-name').value = '';
            document.getElementById('playlist-visibility').value = 'public';
            document.getElementById('playlist-modal').classList.remove('hidden');
        }

        function editPlaylist(id, name, visibility) {
            document.getElementById('modal-title').textContent = 'Edit Playlist';
            document.getElementById('playlist-form').action = '/playlists/' + id;
            document.getElementById('form-method').value = 'PUT';
            document.getElementById('playlist-name').value = name;
            document.getElementById('playlist-visibility').value = visibility;
            document.getElementById('playlist-modal').classList.remove('hidden');
        }

        function closePlaylistModal() {
            document.getElementById('playlist-modal').classList.add('hidden');
        }
    </script>
    @endpush
</x-main-layout>
