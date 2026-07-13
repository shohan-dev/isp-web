<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'Ticket Details',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Support Tickets', 'url' => route_to('route.ticket') . '?id=' . (int) $details->id],
        ['label' => 'Ticket Details'],
      ],
      'actions' => '<a class="btn btn-default" href="' . route_to('route.ticket') . '?id=' . (int) $details->id . '"><i class="fa fa-inbox" aria-hidden="true"></i> Inbox</a>',
    ]); ?>

    <div class="ipb-ticket-inbox card ipb-ticket-inbox-solo">
      <div class="ipb-ticket-inbox-pane is-solo">
        <?= $this->include('tickets/partials/thread', [
          'details' => $details,
          'canReply' => $canReply,
          'ticketUser' => $ticketUser ?? null,
        ]); ?>
      </div>
    </div>

  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
(function ($) {
  function scrollMessages() {
    var el = $('.ipb-ticket-messages')[0];
    if (el) el.scrollTop = el.scrollHeight;
  }

  $(document).ready(function () {
    scrollMessages();
  });

  <?php
    $canTransfer = in_array(getSession('user_role'), ['admin', 'super_admin', 'resellerAdmin', 'employee'], true);
    $resellers = $resellers ?? [];
  ?>
  <?php if ($canTransfer): ?>
  $(document).on('click', '.customer-transfer-button', function () {
    var ticketIds = [<?= (int) $details->id; ?>];
    <?php if (empty($resellers)): ?>
    tata.error('No employees available', 'No employees available to transfer to.');
    return;
    <?php endif; ?>

    var employeeDropdown = `<div class="employee-dropdown-container" style="max-height: 180px; overflow-y: auto; border: 1px solid var(--border); padding: 8px; width: 100%; border-radius: 8px; text-align: left;">
    <?php foreach ($resellers as $employee): ?>
        <label style="display: flex; align-items: center; gap: 8px; padding: 6px 4px; cursor: pointer;">
            <input type="checkbox" class="employee-checkbox" value="<?= (int) $employee->id ?>">
            <span><?= esc($employee->name); ?></span>
        </label>
    <?php endforeach; ?>
    </div>`;

    swal({
      title: "Transfer Ticket",
      text: "Select employee(s) to transfer this ticket:",
      content: { element: "div", attributes: { innerHTML: employeeDropdown } },
      buttons: {
        cancel: "Cancel",
        confirm: { text: "Transfer", closeModal: false },
      },
    }).then(function (confirmed) {
      if (!confirmed) return;
      var selectedEmployees = [];
      $('.employee-checkbox:checked').each(function () {
        selectedEmployees.push($(this).val());
      });
      if (!selectedEmployees.length) {
        swal("Error", "Please select at least one employee.", "error");
        return;
      }
      $.ajax({
        url: <?= json_encode(route_to('route.ticket.transfer')); ?>,
        type: 'POST',
        data: { tickets: ticketIds, employees: selectedEmployees },
        headers: { <?= json_encode(csrf_header()); ?>: <?= json_encode(csrf_hash()); ?> },
        success: function (result) {
          swal.close();
          tata.success('Ticket transferred', result.response);
        },
        error: function (response) {
          swal.close();
          try {
            tata.error("Couldn't transfer ticket", jQuery.parseJSON(response.responseText).response);
          } catch (e) {
            tata.error("Couldn't transfer ticket", 'Transfer failed');
          }
        }
      });
    });
  });
  <?php endif; ?>

  <?php if ($canReply && ($details->status != 'closed') && userHasPermission('support_ticket', 'send_msg')): ?>
  $(document).on('submit', '#ipbTicketReplyForm', function (event) {
    event.preventDefault();
    var form = this;
    var $btn = $(form).find('button[type="submit"]');

    $.ajax({
      url: <?= json_encode(route_to('route.ticket.sendmsg', $details->id)); ?>,
      type: 'POST',
      data: new FormData(form),
      contentType: false,
      cache: false,
      processData: false,
      beforeSend: function () {
        $btn.html("<i class='fas fa-spinner fa-spin' aria-hidden='true'></i> Please wait");
        $btn.attr('disabled', 'true');
      },
      success: function (result) {
        $btn.html("<i class='fa fa-paper-plane' aria-hidden='true'></i> Send");
        $btn.removeAttr('disabled');
        $(form).trigger('reset');

        var msg = (result.response && result.response.msg) ? result.response.msg : '';
        var sender = (result.response && result.response.sender) ? result.response.sender : '';
        var datetime = (result.response && result.response.datetime) ? result.response.datetime : '';

        var $msgs = $('.ipb-ticket-messages');
        $msgs.find('.ipb-ticket-empty').remove();
        $msgs.append(
          '<div class="ipb-ticket-msg is-mine">' +
            '<img class="ipb-ticket-avatar" src="<?= esc(base_url('assets/img/icon/avatar.png'), 'attr'); ?>" alt="">' +
            '<div class="ipb-ticket-bubble-wrap">' +
              '<div class="ipb-ticket-bubble"></div>' +
              '<div class="ipb-ticket-msg-meta"></div>' +
            '</div>' +
          '</div>'
        );
        var $last = $msgs.find('.ipb-ticket-msg').last();
        $last.find('.ipb-ticket-bubble').text(msg);
        $last.find('.ipb-ticket-msg-meta').text(sender + ' · ' + datetime);
        scrollMessages();
      },
      error: function (response) {
        $btn.html("<i class='fa fa-paper-plane' aria-hidden='true'></i> Send");
        $btn.removeAttr('disabled');
        try {
          tata.error("Couldn't send message", jQuery.parseJSON(response.responseText).response);
        } catch (e) {
          tata.error("Couldn't send message", 'Could not send message');
        }
      }
    });
  });
  <?php endif; ?>
})(jQuery);
</script>

<?= $this->endSection('script'); ?>
