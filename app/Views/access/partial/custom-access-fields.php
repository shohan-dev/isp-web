<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal">&times;</button>
  <h4 class="modal-title"><?= $heading; ?></h4>
</div>

<?= form_open($form_submit_link, 'class="form"'); ?>
<div class="modal-body">
  <div class="form-group">
    <label>User</label>
    <select name="user" class="form-control">
      <?php
      echo '<option value="">--Select--</option>';
      if (session()->get('user_role') === 'super_admin' && !empty($admins)):
        echo '<optgroup label="Admins List">';
        foreach ($admins as $c):
          $s = ($user_id === $c->id) ? 'selected' : null;
          echo '<option value="' . $c->id . '" ' . $s . '>' . $c->id . '-' . $c->name . '-' . $c->mobile . '</option>';
        endforeach;
        echo '</optgroup>';
      endif;
      if (!empty($customers)):
        echo '<optgroup label="Customer List">';
        foreach ($customers as $c):
          $s = ($user_id === $c->id) ? 'selected' : null;
          echo '<option value="' . $c->id . '" ' . $s . '>' . $c->id . '-' . $c->name . '-' . $c->mobile . '</option>';
        endforeach;
        echo '</optgroup>';
      endif;
      if (!empty($employees)):
        echo '<optgroup label="Employee List">';
        foreach ($employees as $e):
          $s = ($user_id === $e->id) ? 'selected' : null;
          echo '<option value="' . $e->id . '" ' . $s . '>' . $e->id . '-' . $e->name . '-' . $e->mobile . '</option>';
        endforeach;
        echo '</optgroup>';
      endif;
      if (!empty($resellers)):
        echo '<optgroup label="Reseller List">';
        foreach ($resellers as $r):
          $s = ($user_id === $r->id) ? 'selected' : null;
          echo '<option value="' . $r->id . '" ' . $s . '>' . $r->id . '-' . $r->name . '-' . $r->mobile . '</option>';
        endforeach;
        echo '</optgroup>';
      endif; ?>
    </select>
    <button type="button" id="toggle-all-permissions" class="btn btn-sm btn-primary" style="margin-bottom:10px;margin-top:20px;">Select All</button>
    <small id="user-error" class="error text-danger"></small>
  </div>

  <div class="form-group" style="margin-bottom:25px;">
    <label>Status</label>
    <div class="radio">
      <label class="radio-inline"><?= form_radio(['name'=>'status','value'=>'active','checked'=>!empty($permission_status) ? ($permission_status==='active') : false]); ?> Active</label>
      <label class="radio-inline"><?= form_radio(['name'=>'status','value'=>'inactive','checked'=>!empty($permission_status) ? ($permission_status==='inactive') : false]); ?> Inactive</label>
    </div>
    <small id="status-error" class="error text-danger"></small>
  </div>

  <h4 class="text-primary" style="margin-bottom:20px;">Access List</h4>

  <?php
  $sections = [
    'area' => ['View','Create','Delete','Update'],
    'packages' => ['View','Create','Delete','Update'],
    'customer' => ['View','Create','Delete','Update','Update Subscription','Update Connection','Free User Create'],
    'employee' => ['View','Create','Delete','Update'],
    'customer_payment' => ['View','Create','Delete','Update','Invoice Download'],
    'employee_payment' => ['View','Create','Delete','Update'],
    'Resellers' => ['View','Create','Delete','Update','update_conn','update_subscription','self_recharge','daily_payment_generate'],
    'network' => ['View','Create','Delete','Update'],
    'hotspot' => ['View','Create','Delete','Update'],
    'olt' => ['View','Create','Delete','Update'],
    'accounting' => ['View','Create','Delete','Update'],
    'inventory_purchess' => ['View','Create','Delete','Update'],
    'support_ticket' => ['View','Create','Send Message','Delete','Update'],
    'referral' => ['View','Update'],
    'recycle_bin' => ['View','Restore','Delete Forever','Empty Trash'],
    'sms_message' => ['View','Create','Delete'],
    'reports' => ['View','Create','Delete','Update'],
    'software_settings' => ['View','Update'],
    'payment' => ['View','Online Payment (Only for Customers)','Invoice Download'],
    'subscription' => ['View','Renew'],
    'routers' => ['View','Create','Delete','Update','Sync Users'],
    'profile_update' => ['View','Update'],
    'password_change' => ['Update'],
    'ai_chat' => ['Access Chat']
  ];

  $valueMap = [
    'View'=>'read','Create'=>'create','Delete'=>'delete','Update'=>'update',
    'Update Subscription'=>'update_subscription','Update Connection'=>'update_conn',
    'Free User Create'=>'free_customer_create',
    'Invoice Download'=>'invoice','Online Payment (Only for Customers)'=>'payment',
    'Send Message'=>'send_msg','Sync Users'=>'sync','Renew'=>'renew',
    'update_conn'=>'update_conn','update_subscription'=>'update_subscription',
    'self_recharge'=>'self_recharge','daily_payment_generate'=>'daily_payment_generate',
    'Restore'=>'restore','Delete Forever'=>'delete_forever','Empty Trash'=>'empty',
    'Access Chat'=>'chat'
  ];

  foreach ($sections as $key => $labels):
    $name = $key.'[]';
  ?>
  <div class="form-group">
    <label><?= ucwords(str_replace('_',' ',$key)); ?></label>
    <div class="checkbox">
      <?php foreach ($labels as $label):
        $val = isset($valueMap[$label]) ? $valueMap[$label] : strtolower(str_replace(' ','_',$label));
        $checked = userHasPermission($key, $val, $user_type, $user_id);
      ?>
      <label class="checkbox-inline">
        <?= form_checkbox(['name'=>$name,'value'=>$val,'checked'=>$checked]); ?> <?= $label; ?>
      </label>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<div class="modal-footer">
  <?= form_button(["content"=>$btn_text,"class"=>"btn btn-warning","type"=>"submit"]); ?>
  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>
<?= form_close(); ?>

<script>
$(document).ready(function(){
  $('select[class="form-control"]').select2({width:'100%'});
  let allChecked = false;
  $('#toggle-all-permissions').on('click',function(){
    allChecked = !allChecked;
    $('input[type="checkbox"]').prop('checked',allChecked);
    $(this).text(allChecked ? 'Deselect All' : 'Select All');
  });
});
</script>