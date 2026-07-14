<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal">&times;</button>
  <h4 class="modal-title"><?= $heading ?></h4>
</div>

<?= form_open($form_submit_link, 'class="form"') ?>

<div class="modal-body">
  <?php
  // Define permission sections with their actions and labels
  $sections = [
    'area' => ['label' => 'Service Areas', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update']],
    'packages' => ['label' => 'Packages', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update']],
    'customer' => ['label' => 'Customers', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update', 'update_subscription' => 'Update Subscription', 'update_conn' => 'Update Connection', 'free_customer_create' => 'Free User Create']],
    'employee' => ['label' => 'Employees', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update']],
    'Resellers' => ['label' => 'Resellers', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update', 'update_subscription' => 'Update Subscription', 'update_conn' => 'Update Connection', 'self_recharge' => 'Self Recharge', 'daily_payment_generate' => 'Daily Bill Generate']],
    'customer_payment' => ['label' => 'Customers Payment', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update', 'invoice' => 'Invoice Download']],
    'employee_payment' => ['label' => 'Employees Payment', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update']],
    'inventory_purchess' => ['label' => 'Inventory & Purchess', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update']],
    'network' => ['label' => 'Network', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update']],
    'hotspot' => ['label' => 'Hotspot', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update']],
    'olt' => ['label' => 'OLT', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update']],
    'accounting' => ['label' => 'Accounting', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update']],
    'support_ticket' => ['label' => 'Support Tickets', 'actions' => ['read' => 'View', 'create' => 'Create', 'send_msg' => 'Send Message', 'delete' => 'Delete', 'update' => 'Update']],
    'referral' => ['label' => 'Referral & Reward', 'actions' => ['read' => 'View', 'update' => 'Update']],
    'recycle_bin' => ['label' => 'Recycle Bin', 'actions' => ['read' => 'View', 'restore' => 'Restore', 'delete_forever' => 'Delete Forever', 'empty' => 'Empty Trash']],
    'sms_message' => ['label' => 'SMS', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete']],
    'reports' => ['label' => 'Reports', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update']],
    'software_settings' => ['label' => 'Software Settings', 'actions' => ['read' => 'View', 'update' => 'Update']],
    'routers' => ['label' => 'Mikrotik Routers', 'actions' => ['read' => 'View', 'create' => 'Create', 'delete' => 'Delete', 'update' => 'Update', 'sync' => 'Sync Users']],
    'profile_update' => ['label' => 'Profile Update', 'actions' => ['read' => 'View', 'update' => 'Update']],
    'password_change' => ['label' => 'Change Password', 'actions' => ['update' => 'Update']],
    'ai_chat' => ['label' => 'AI Chat Assistant', 'actions' => ['chat' => 'Access Chat']],
  ];

  foreach ($sections as $key => $section): ?>
    <div class="form-group">
      <label><?= $section['label'] ?></label>
      <div class="checkbox">
        <?php foreach ($section['actions'] as $value => $label): ?>
          <label class="checkbox-inline">
            <?= form_checkbox([
              'name'    => "{$key}[]",
              'value'   => $value,
              'checked' => in_array($value, $permissions[$key] ?? [])
            ]) ?>
            <?= $label ?>
          </label>
        <?php endforeach ?>
      </div>
    </div>
  <?php endforeach ?>

  <!-- Special Cases -->
  <div class="form-group">
    <label>Payment Records</label>
    <div class="checkbox">
      <?php foreach (['read' => 'View', 'invoice' => 'Invoice Download'] as $value => $label): ?>
        <label class="checkbox-inline">
          <?= form_checkbox([
            'name'    => 'payment[]',
            'value'   => $value,
            'checked' => in_array($value, $permissions['payment'] ?? [])
          ]) ?>
          <?= $label ?>
        </label>
      <?php endforeach ?>
      <?php if (in_array($user_type, ['user', 'admin', 'resellerAdmin'])): ?>
        <label class="checkbox-inline">
          <?= form_checkbox([
            'name'    => 'payment[]',
            'value'   => 'payment',
            'checked' => in_array('payment', $permissions['payment'] ?? [])
          ]) ?>
          Online Payment
        </label>
      <?php endif ?>
    </div>
  </div>

  <?php if (in_array($user_type, ['user', 'admin', 'resellerAdmin'])): ?>
    <div class="form-group">
      <label>Subscription</label>
      <div class="checkbox">
        <?php foreach (['read' => 'View', 'renew' => 'Renew'] as $value => $label): ?>
          <label class="checkbox-inline">
            <?= form_checkbox([
              'name'    => 'subscription[]',
              'value'   => $value,
              'checked' => in_array($value, $permissions['subscription'] ?? [])
            ]) ?>
            <?= $label ?>
          </label>
        <?php endforeach ?>
      </div>
    </div>
  <?php endif ?>
</div>

<div class="modal-footer">
  <?= form_button([
    "content" => $btn_text,
    "class"   => "btn btn-warning",
    "type"    => "submit",
  ]) ?>
  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>
<?= form_close() ?>