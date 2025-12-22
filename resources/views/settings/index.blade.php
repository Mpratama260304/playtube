<x-main-layout>
    <x-slot name="title">Settings - {{ config('app.name') }}</x-slot>

    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold text-white mb-8">Settings</h1>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-900/50 border border-green-500 text-green-300 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Profile Settings -->
        <div class="bg-gray-800 rounded-xl p-6 mb-6">
            <h2 class="text-lg font-bold text-white mb-4">Profile Information</h2>
            <form action="{{ route('settings.profile') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="flex items-center space-x-6 mb-6">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-20 h-20 rounded-full object-cover">
                    @else
                        <div class="w-20 h-20 rounded-full bg-red-600 flex items-center justify-center text-white text-2xl font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <label class="px-4 py-2 bg-gray-700 text-white rounded-lg cursor-pointer hover:bg-gray-600 transition-colors">
                            Change Avatar
                            <input type="file" name="avatar" accept="image/*" class="hidden">
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Name</label>
                        <input type="text" name="name" value="{{ auth()->user()->name }}" required
                               class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-gray-500">
                        @error('name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                        <input type="text" name="username" value="{{ auth()->user()->username }}" required
                               class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-gray-500">
                        @error('username')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email" name="email" value="{{ auth()->user()->email }}" required
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-gray-500">
                    @error('email')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Bio</label>
                    <textarea name="bio" rows="3"
                              class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-gray-500 resize-none">{{ auth()->user()->bio }}</textarea>
                    @error('bio')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="px-4 py-2 bg-white text-gray-900 rounded-lg hover:bg-gray-200">
                    Save Changes
                </button>
            </form>
        </div>

        <!-- Password Settings -->
        <div class="bg-gray-800 rounded-xl p-6 mb-6">
            <h2 class="text-lg font-bold text-white mb-4">Change Password</h2>
            <form action="{{ route('settings.password') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Current Password</label>
                    <input type="password" name="current_password" required
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-gray-500">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">New Password</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-gray-500">
                        @error('password')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-gray-500">
                    </div>
                </div>

                <button type="submit" class="px-4 py-2 bg-white text-gray-900 rounded-lg hover:bg-gray-200">
                    Update Password
                </button>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="bg-red-900/20 border border-red-500/30 rounded-xl p-6">
            <h2 class="text-lg font-bold text-red-400 mb-4">Danger Zone</h2>
            <p class="text-gray-400 mb-4">Once you delete your account, there is no going back. Please be certain.</p>
            <form action="{{ route('settings.delete-account') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Delete Account
                </button>
            </form>
        </div>
    </div>
</x-main-layout>
