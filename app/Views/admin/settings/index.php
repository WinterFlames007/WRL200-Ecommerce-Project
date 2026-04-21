<div class="admin-page">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-brand">StoreName</div>

            <nav class="admin-sidebar-nav">
                <a href="/admin/dashboard">Dashboard</a>
                <a href="/admin/users">User List</a>
                <a href="/admin/settings" class="active">Settings</a>

                <a href="/admin/reports">Reports</a>
                <a href="/admin/logs">Logs</a>

                <a href="/logout">Logout</a>
            </nav>
        </aside>

        <div class="admin-main">
            <div class="admin-topbar">
                <h1>Platform Settings</h1>
                <div class="admin-user-greet">
                    Hello, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin') ?>
                </div>
            </div>



            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>


            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Please fix the following issues:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="/admin/settings" class="settings-form">
                <div class="settings-card">
                    <div class="settings-card-title">Security Settings</div>

                    <div class="settings-row">
                        <label>
                            Enable Two-Factor Authentication
                        </label>
                        <input type="checkbox" name="two_factor_enabled" <?= $settings['two_factor_enabled'] === '1' ? 'checked' : '' ?>>
                    </div>

                    <div class="settings-row">
                        <label for="minimum_password_length">Minimum Password Length</label>
                        <select name="minimum_password_length" id="minimum_password_length">
                            <option value="6" <?= $settings['minimum_password_length'] === '6' ? 'selected' : '' ?>>6</option>
                            <option value="8" <?= $settings['minimum_password_length'] === '8' ? 'selected' : '' ?>>8</option>
                            <option value="10" <?= $settings['minimum_password_length'] === '10' ? 'selected' : '' ?>>10</option>
                            <option value="12" <?= $settings['minimum_password_length'] === '12' ? 'selected' : '' ?>>12</option>
                        </select>
                    </div>

                    <div class="settings-row">
                        <label for="session_timeout">Session Timeout</label>
                        <select name="session_timeout" id="session_timeout">
                            <option value="15 Minutes" <?= $settings['session_timeout'] === '15 Minutes' ? 'selected' : '' ?>>15 Minutes</option>
                            <option value="30 Minutes" <?= $settings['session_timeout'] === '30 Minutes' ? 'selected' : '' ?>>30 Minutes</option>
                            <option value="1 Hour" <?= $settings['session_timeout'] === '1 Hour' ? 'selected' : '' ?>>1 Hour</option>
                        </select>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-title">Payment Settings</div>

                    <div class="settings-row">
                        <label for="stripe_api_key">Stripe API Key</label>
                        <input type="text" name="stripe_api_key" id="stripe_api_key" value="<?= htmlspecialchars($settings['stripe_api_key']) ?>">
                    </div>

                    <div class="settings-row">
                        <label for="webhook_url">Webhook URL</label>
                        <input type="text" name="webhook_url" id="webhook_url" value="<?= htmlspecialchars($settings['webhook_url']) ?>">
                    </div>

                    <div class="settings-row">
                        <label for="mode">Mode</label>
                        <select name="mode" id="mode">
                            <option value="Test Mode" <?= $settings['mode'] === 'Test Mode' ? 'selected' : '' ?>>Test Mode</option>
                            <option value="Live Mode" <?= $settings['mode'] === 'Live Mode' ? 'selected' : '' ?>>Live Mode</option>
                        </select>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-title">Email Settings</div>

                    <div class="settings-row">
                        <label for="smtp_host">SMTP Host</label>
                        <input type="text" name="smtp_host" id="smtp_host" value="<?= htmlspecialchars($settings['smtp_host']) ?>">
                    </div>

                    <div class="settings-row">
                        <label for="support_email">Support Email Address</label>
                        <input type="email" name="support_email" id="support_email" value="<?= htmlspecialchars($settings['support_email']) ?>">
                    </div>

                    <div class="settings-row">
                        <label>
                            Enable Email Notification
                        </label>
                        <input type="checkbox" name="email_notifications" <?= $settings['email_notifications'] === '1' ? 'checked' : '' ?>>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-title">System Settings</div>

                    <div class="settings-row">
                        <label>
                            Maintenance Mode
                        </label>
                        <input type="checkbox" name="maintenance_mode" <?= $settings['maintenance_mode'] === '1' ? 'checked' : '' ?>>
                    </div>

                    <div class="settings-row">
                        <label for="backup_schedule">Backup Schedule</label>
                        <select name="backup_schedule" id="backup_schedule">
                            <option value="Daily at 02:00 AM" <?= $settings['backup_schedule'] === 'Daily at 02:00 AM' ? 'selected' : '' ?>>Daily at 02:00 AM</option>
                            <option value="Weekly on Sunday" <?= $settings['backup_schedule'] === 'Weekly on Sunday' ? 'selected' : '' ?>>Weekly on Sunday</option>
                            <option value="Monthly on 1st" <?= $settings['backup_schedule'] === 'Monthly on 1st' ? 'selected' : '' ?>>Monthly on 1st</option>
                        </select>
                    </div>

                    <div class="settings-row">
                        <label for="system_version">System Version</label>
                        <input type="text" name="system_version" id="system_version" value="<?= htmlspecialchars($settings['system_version']) ?>">
                    </div>
                </div>

                <div class="settings-save-row">
                    <button type="submit" class="button settings-save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>