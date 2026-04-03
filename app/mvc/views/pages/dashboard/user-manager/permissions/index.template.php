{{-- Permissions Index --}}

<div class="p-8">
    {{-- Page Header --}}
    <div class="mb-8">
    </div>

    {{-- Permissions List --}}
    <div class="space-y-6">
        @foreach ($permissions as $category => $categoryPermissions)
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl overflow-hidden">
                <div class="bg-slate-900/50 px-6 py-4 border-b border-slate-700/50">
                    <h3 class="text-lg font-semibold text-theme-primary uppercase tracking-wide">
                        {{ e($category) }}
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($categoryPermissions as $permission)
                            @php
                            $permissionArray = is_object($permission) ? $permission->toArray() : $permission;
                            @endphp
                            <div class="bg-slate-900/30 rounded-lg p-4 border border-slate-700/50 hover:border-theme-primary/50 transition-colors">
                                <div class="flex items-start gap-3">
                                    <ion-icon name="key-outline" class="text-theme-primary text-xl flex-shrink-0 mt-0.5"></ion-icon>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-white mb-1">
                                            {{ e($permissionArray['name'] ?? '') }}
                                        </div>
                                        <div class="text-xs text-slate-400 font-mono mb-2 break-all">
                                            {{ e($permissionArray['slug'] ?? '') }}
                                        </div>
                                        @if (!empty($permissionArray['description']))
                                            <div class="text-xs text-slate-500">
                                                {{ e($permissionArray['description']) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
