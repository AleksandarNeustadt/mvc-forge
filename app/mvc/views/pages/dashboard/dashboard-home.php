<div class="p-8">
    
    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Welcome Card -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-8 mb-8">
        <div class="text-center">
            <ion-icon name="grid-outline" class="text-6xl text-theme-primary mb-4"></ion-icon>
            <h2 class="text-2xl font-semibold text-white mb-2">Welcome to Dashboard</h2>
            <p class="text-slate-400 mb-6">This is your central control panel. Use the navigation menu to access different management sections.</p>
            
            <!-- Quick Links -->
            <div class="flex flex-wrap justify-center gap-4 mt-6">
                <a href="<?= route('dashboard.users') ?>" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700/50 hover:bg-slate-700 text-white font-medium rounded-lg transition-colors">
                    <ion-icon name="people-outline"></ion-icon>
                    User Management
                </a>
                <a href="<?= route('dashboard.database') ?>" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700/50 hover:bg-slate-700 text-white font-medium rounded-lg transition-colors">
                    <ion-icon name="server-outline"></ion-icon>
                    Database Management
                </a>
            </div>
        </div>
    </div>

    <!-- Placeholder for future widgets -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Widget placeholders will go here -->
    </div>

</div>

