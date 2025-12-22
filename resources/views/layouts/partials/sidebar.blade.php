<!-- Sidebar -->
<aside class="hidden lg:flex lg:flex-col w-64 bg-gray-900 border-r border-gray-800 flex-shrink-0"
       :class="{ 'lg:w-64': sidebarOpen, 'lg:w-20': !sidebarOpen }">
    @include('layouts.partials.sidebar-content')
</aside>
