<div class="p-8">
    
    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm mb-1">Total Requests</p>
                    <p class="text-3xl font-bold text-white"><?= number_format($totalRequests ?? 0) ?></p>
                </div>
                <ion-icon name="globe-outline" class="text-4xl text-theme-primary"></ion-icon>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm mb-1">Unique IPs</p>
                    <p class="text-3xl font-bold text-white"><?= number_format(count($ipStats ?? [])) ?></p>
                </div>
                <ion-icon name="location-outline" class="text-4xl text-blue-400"></ion-icon>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm mb-1">Suspicious</p>
                    <p class="text-3xl font-bold text-red-400"><?= number_format($suspiciousCount ?? 0) ?></p>
                </div>
                <ion-icon name="warning-outline" class="text-4xl text-red-400"></ion-icon>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm mb-1">Countries</p>
                    <p class="text-3xl font-bold text-white"><?= number_format(count($countryStats ?? [])) ?></p>
                </div>
                <ion-icon name="flag-outline" class="text-4xl text-green-400"></ion-icon>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6 mb-8">
        <?php global $router; $lang = $router->lang ?? 'sr'; ?>
        <form method="GET" action="/<?= $lang ?>/dashboard/ip-tracking" class="flex gap-4">
            <div class="flex-1">
                <label for="ip" class="block text-sm font-medium text-slate-300 mb-2">Filter by IP Address</label>
                <input type="text" 
                       id="ip" 
                       name="ip" 
                       value="<?= e($ipFilter ?? '') ?>"
                       placeholder="e.g., 192.168.1.1"
                       class="w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-theme-primary">
            </div>
            <div class="flex items-end">
                <button type="submit" 
                        class="px-6 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors">
                    Filter
                </button>
                <?php if (!empty($ipFilter)): ?>
                    <a href="/<?= $lang ?>/dashboard/ip-tracking" 
                       class="ml-2 px-6 py-2 bg-slate-700 hover:bg-slate-600 text-white font-medium rounded-lg transition-colors">
                        Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- IP Statistics Table -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl overflow-hidden mb-8">
        <div class="p-6 border-b border-slate-700/50">
            <h2 class="text-2xl font-bold text-white">IP Statistics</h2>
            <p class="text-slate-400 text-sm mt-1">Top IP addresses by request count</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Service</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Requests</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Suspicious</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Last Seen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php if (empty($ipStats)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <ion-icon name="analytics-outline" class="text-4xl mb-3"></ion-icon>
                                <p>No IP statistics available</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($ipStats, 0, 20) as $stat): ?>
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-sm text-theme-primary font-mono"><?= e($stat['ip_address'] ?? '') ?></code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if (!empty($stat['known_service'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-500/20 text-blue-300 rounded-md text-xs font-medium">
                                            <ion-icon name="cloud-outline" class="text-xs"></ion-icon>
                                            <?= e($stat['known_service']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300">
                                    <?= e($stat['country_name'] ?? 'Unknown') ?>
                                    <?php if (!empty($stat['country_code'])): ?>
                                        <span class="text-slate-500 text-xs">(<?= e($stat['country_code']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300">
                                    <?php if (!empty($stat['username'])): ?>
                                        <span class="text-white"><?= e($stat['username']) ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-500">Guest</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-white font-medium">
                                    <?= number_format($stat['request_count'] ?? 0) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (($stat['suspicious_count'] ?? 0) > 0): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900/50 text-red-300">
                                            <?= number_format($stat['suspicious_count']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-sm">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                                    <?= !empty($stat['last_seen']) ? date('Y-m-d H:i:s', is_int($stat['last_seen']) ? $stat['last_seen'] : strtotime($stat['last_seen'])) : 'N/A' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity Table -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl overflow-hidden">
        <div class="p-6 border-b border-slate-700/50 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-white">Recent Activity</h2>
                <p class="text-slate-400 text-sm mt-1">Latest requests from all IP addresses</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Service</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">User</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Path</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php if (empty($recentEntries)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                <ion-icon name="file-tray-outline" class="text-4xl mb-3"></ion-icon>
                                <p>No activity recorded</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        // Get unique users by IP for display (users who have logged in from this IP before)
                        $ipUserMap = [];
                        if (!empty($recentEntries)) {
                            $ipAddresses = array_unique(array_column($recentEntries, 'ip_address'));
                            foreach ($ipAddresses as $ip) {
                                $usersFromIp = Database::select(
                                    "SELECT DISTINCT user_id, username FROM ip_tracking WHERE ip_address = ? AND user_id IS NOT NULL AND username IS NOT NULL LIMIT 3",
                                    [$ip]
                                );
                                if (!empty($usersFromIp)) {
                                    $ipUserMap[$ip] = $usersFromIp;
                                }
                            }
                        }
                        ?>
                        <?php foreach ($recentEntries as $entry): ?>
                            <tr class="hover:bg-slate-800/30 transition-colors <?= ($entry['is_suspicious'] ?? false) ? 'bg-red-900/10' : '' ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                                    <?= !empty($entry['created_at']) ? date('Y-m-d H:i:s', is_int($entry['created_at']) ? $entry['created_at'] : strtotime($entry['created_at'])) : 'N/A' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-sm text-theme-primary font-mono"><?= e($entry['ip_address'] ?? '') ?></code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if (!empty($entry['known_service'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-500/20 text-blue-300 rounded-md text-xs font-medium">
                                            <ion-icon name="cloud-outline" class="text-xs"></ion-icon>
                                            <?= e($entry['known_service']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300">
                                    <div class="flex items-center gap-2">
                                        <?php 
                                        // Display flag - prioritize language_country_code, then country_code
                                        $flagCode = null;
                                        $countryName = $entry['country_name'] ?? 'Unknown';
                                        
                                        if (!empty($entry['language_country_code'])) {
                                            $flagCode = strtolower($entry['language_country_code']);
                                        } elseif (!empty($entry['country_code'])) {
                                            $flagCode = strtolower($entry['country_code']);
                                        }
                                        
                                        if ($flagCode): ?>
                                            <span class="fi fi-<?= e($flagCode) ?>" style="font-size: 1.5rem;" title="<?= e($countryName) ?>"></span>
                                        <?php else: ?>
                                            <span class="text-slate-500 text-lg">🌐</span>
                                        <?php endif; ?>
                                        <div class="flex flex-col">
                                            <div class="text-white font-medium"><?= e($countryName) ?></div>
                                            <?php if (!empty($entry['country_code'])): ?>
                                                <span class="text-slate-400 text-xs"><?= e(strtoupper($entry['country_code'])) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($entry['language_code']) && !empty($entry['language_name'])): ?>
                                                <span class="text-slate-500 text-xs mt-0.5">
                                                    <?= e($entry['language_name']) ?> (<?= e($entry['language_code']) ?>)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300">
                                    <?php if (!empty($entry['username'])): ?>
                                        <span class="text-white"><?= e($entry['username']) ?></span>
                                    <?php elseif (!empty($ipUserMap[$entry['ip_address'] ?? ''] ?? [])): ?>
                                        <span class="text-slate-400 text-xs" title="Known users from this IP: <?= e(implode(', ', array_column($ipUserMap[$entry['ip_address']], 'username'))) ?>">
                                            Guest (known: <?= e(implode(', ', array_slice(array_column($ipUserMap[$entry['ip_address']], 'username'), 0, 2))) ?><?= count($ipUserMap[$entry['ip_address']]) > 2 ? '...' : '' ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-500">Guest</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium 
                                        <?= ($entry['request_method'] ?? 'GET') === 'GET' ? 'bg-blue-900/50 text-blue-300' : 'bg-purple-900/50 text-purple-300' ?>">
                                        <?= e($entry['request_method'] ?? 'GET') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-300">
                                    <code class="text-xs"><?= e(substr($entry['request_path'] ?? '/', 0, 60)) ?><?= strlen($entry['request_path'] ?? '') > 60 ? '...' : '' ?></code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($entry['is_suspicious'] ?? false): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900/50 text-red-300">
                                            <ion-icon name="warning" class="mr-1"></ion-icon>
                                            Suspicious
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900/50 text-green-300">
                                            Normal
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="p-6 border-t border-slate-700/50 flex items-center justify-between">
                <div class="text-sm text-slate-400">
                    Showing page <?= $currentPage ?> of <?= $totalPages ?> (<?= number_format($totalRequests) ?> total requests)
                </div>
                <div class="flex gap-2">
                    <?php if ($currentPage > 1): ?>
                        <a href="/<?= $lang ?>/dashboard/ip-tracking?page=<?= $currentPage - 1 ?><?= !empty($ipFilter) ? '&ip=' . urlencode($ipFilter) : '' ?>" 
                           class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white font-medium rounded-lg transition-colors">
                            Previous
                        </a>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="/<?= $lang ?>/dashboard/ip-tracking?page=<?= $currentPage + 1 ?><?= !empty($ipFilter) ? '&ip=' . urlencode($ipFilter) : '' ?>" 
                           class="px-4 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

