<div class="form-group">
    <label>Enable RADIUS Integration</label>
    <div class="radio">
        <label class="radio-inline">
            <input type="radio" name="enable_radius" value="yes" <?= getSetting('enable_radius', 'no') === 'yes' ? 'checked' : ''; ?>> Yes
        </label>
        <label class="radio-inline">
            <input type="radio" name="enable_radius" value="no" <?= getSetting('enable_radius', 'no') === 'no' ? 'checked' : ''; ?>> No
        </label>
    </div>
    <small class="text-muted">If set to 'No', the system will bypass all RADIUS-related automation even if the info below is filled.</small>
</div>

<hr>

<div class="form-group">
    <label>RADIUS Server IP</label>
    <?= form_input([
        'name'  => 'radius_server_ip',
        'class' => 'form-control',
        'placeholder' => 'Ex: 203.18.158.157',
        'value' => getSetting('radius_server_ip', '203.18.158.157')
    ]); ?>
    <small id="radius_server_ip-error" class="error text-danger"></small>
    <small class="text-muted">The IP address of your FreeRADIUS server.</small>
</div>

<div class="form-group">
    <label>RADIUS Shared Secret</label>
    <?= form_input([
        'name'  => 'radius_secret',
        'class' => 'form-control',
        'placeholder' => 'Ex: ISP_Secret_123',
        'value' => getSetting('radius_secret', 'ISP_Secret_123')
    ]); ?>
    <small id="radius_secret-error" class="error text-danger"></small>
    <small class="text-muted">The shared secret configured in your RADIUS clients.conf file.</small>
</div>

<div class="well well-sm">
    <h4><i class="fa fa-info-circle"></i> Quick Guide</h4>
    <p>This information is used to automatically configure your MikroTik routers to use the RADIUS server for PPP authentication.</p>
    <ul>
        <li><strong>Server IP:</strong> Ensure your MikroTik can reach this IP.</li>
        <li><strong>Secret:</strong> Must match the secret defined in your VPS <code>/etc/raddb/clients.conf</code>.</li>
    </ul>
</div>
