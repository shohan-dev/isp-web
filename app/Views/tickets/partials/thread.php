<?php
/**
 * Ticket conversation thread (JSX TicketThread parity).
 *
 * @var object      $details
 * @var bool        $canReply
 * @var object|null $ticketUser
 */
$statusClass = 'is-neutral';
$statusLabel = 'Closed';
if (($details->status ?? '') === 'opened') {
  $statusClass = 'is-success';
  $statusLabel = 'Opened';
} elseif (($details->status ?? '') === 'processing') {
  $statusClass = 'is-warning';
  $statusLabel = 'Processing';
} elseif (($details->status ?? '') === 'pending') {
  $statusClass = 'is-warning';
  $statusLabel = 'Pending';
}
$priority = $details->priority ?? null;
$userName = $ticketUser->name ?? (getUserById($details->user_id)->name ?? '—');
$sessionUserId = (string) getSession('user_id');
$avatarUrl = base_url('assets/img/icon/avatar.png');
$canTransfer = in_array(getSession('user_role'), ['admin', 'super_admin', 'resellerAdmin', 'employee'], true);
$canUpdate = userHasPermission('support_ticket', 'update');
$hasThreadActions = $canTransfer || $canUpdate;

$assigneeIds = [];
if (!empty($details->admin_ids)) {
  $decoded = json_decode($details->admin_ids, true);
  $assigneeIds = is_array($decoded) ? $decoded : (is_scalar($decoded) ? [$decoded] : []);
}
$assigneeNames = [];
foreach ($assigneeIds as $assigneeId) {
  $assigneeUser = getUserById((int) $assigneeId);
  if (!empty($assigneeUser->name)) {
    $assigneeNames[] = $assigneeUser->name;
  }
}
?>
<div class="ipb-ticket-thread" data-ticket-id="<?= (int) $details->id; ?>">
  <div class="ipb-ticket-thread-head">
    <div class="ipb-ticket-thread-meta">
      <button type="button" class="ipb-ticket-back" aria-label="Back to ticket list">
        <i class="fa fa-arrow-left" aria-hidden="true"></i>
        <span>Inbox</span>
      </button>
      <div class="ipb-ticket-thread-title-row">
        <div class="ipb-ticket-thread-title"><?= esc($details->subject); ?></div>
        <?php if ($hasThreadActions): ?>
          <div class="ipb-ticket-more ipb-ticket-thread-more">
            <button type="button" class="ipb-ticket-more-btn" aria-haspopup="true" aria-expanded="false" aria-label="More actions" title="More actions">
              <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
            </button>
            <div class="ipb-ticket-more-menu" hidden>
              <?php if ($canUpdate): ?>
                <a class="ipb-ticket-more-item" href="<?= route_to('route.ticket.edit', $details->id); ?>">
                  <i class="far fa-pen-to-square" aria-hidden="true"></i>
                  <span>Update</span>
                </a>
              <?php endif; ?>
              <?php if ($canTransfer): ?>
                <button type="button" class="ipb-ticket-more-item customer-transfer-button">
                  <i class="fa fa-exchange" aria-hidden="true"></i>
                  <span>Transfer</span>
                </button>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <p class="ipb-ticket-thread-sub">
        <?= esc($userName); ?> · Opened <?= date('M j, Y', strtotime($details->datetime)); ?>
        <?php if (!empty($assigneeNames)): ?>
          · Assigned to <strong><?= esc(implode(', ', $assigneeNames)); ?></strong>
        <?php elseif ($canTransfer): ?>
          · <span class="ipb-ticket-unassigned">Unassigned</span>
        <?php endif; ?>
      </p>
      <div class="ipb-ticket-thread-badges">
        <?php if ($priority): ?>
          <span class="ipb-pay-badge <?= $priority === 'high' ? 'is-danger' : ($priority === 'medium' ? 'is-warning' : 'is-info'); ?>">
            <?= esc(ucfirst((string) $priority)); ?>
          </span>
        <?php endif; ?>
        <?php if ($canUpdate):
          $statusOptions = ['opened' => ['Opened', 'is-success'], 'processing' => ['Processing', 'is-warning'], 'closed' => ['Closed', 'is-neutral']];
          $currentTone = $statusOptions[$details->status][1] ?? 'is-neutral';
        ?>
          <div class="ipb-ticket-status-dd" data-ticket-id="<?= (int) $details->id; ?>">
            <button type="button" class="ipb-ticket-status-trigger <?= $currentTone; ?>" aria-haspopup="true" aria-expanded="false">
              <span class="ipb-ticket-status-trigger-label"><?= esc($statusLabel); ?></span>
              <i class="fa fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="ipb-ticket-status-menu" hidden>
              <?php foreach ($statusOptions as $value => [$label, $tone]): ?>
                <button type="button" class="ipb-ticket-status-option <?= $tone; ?><?= $details->status === $value ? ' is-selected' : ''; ?>" data-value="<?= esc($value, 'attr'); ?>">
                  <span class="ipb-ticket-status-dot"></span>
                  <span><?= esc($label); ?></span>
                  <?php if ($details->status === $value): ?><i class="fa fa-check" aria-hidden="true"></i><?php endif; ?>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <span class="ipb-pay-badge <?= $statusClass; ?>"><?= esc($statusLabel); ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="ipb-ticket-messages">
    <?php if (!empty($details->details)): ?>
      <?php
        $messages = json_decode($details->details);
        if (is_array($messages) || is_object($messages)):
          foreach ($messages as $message):
            $isMine = $sessionUserId === (string) ($message->sender ?? '');
            $senderName = getUserById($message->sender)->name ?? '—';
      ?>
        <div class="ipb-ticket-msg<?= $isMine ? ' is-mine' : ''; ?>">
          <img class="ipb-ticket-avatar" src="<?= esc($avatarUrl, 'attr'); ?>" alt="">
          <div class="ipb-ticket-bubble-wrap">
            <div class="ipb-ticket-bubble"><?= esc($message->msg ?? ''); ?></div>
            <div class="ipb-ticket-msg-meta">
              <?= esc($senderName); ?> · <?= esc($message->datetime ?? ''); ?>
            </div>
          </div>
        </div>
      <?php
          endforeach;
        endif;
      ?>
    <?php else: ?>
      <div class="ipb-ticket-empty text-mut">No messages yet.</div>
    <?php endif; ?>
  </div>

  <div class="ipb-ticket-composer">
    <?php if (($details->status ?? '') === 'closed'): ?>
      <p class="ipb-ticket-composer-note is-danger">This ticket is closed.</p>
    <?php elseif ($canReply): ?>
      <?php if (userHasPermission('support_ticket', 'send_msg')): ?>
        <?= form_open(route_to('route.ticket.sendmsg', $details->id), 'id="ipbTicketReplyForm" class="ipb-ticket-reply-form"'); ?>
        <div class="ipb-ticket-composer-row">
          <?= form_input([
            'type'         => 'text',
            'name'         => 'message',
            'placeholder'  => 'Type your reply...',
            'class'        => 'form-control',
            'required'     => 'required',
            'autocomplete' => 'off',
          ]); ?>
          <?= form_button([
            'content' => "<i class='fa fa-paper-plane' aria-hidden='true'></i> Send",
            'class'   => 'btn btn-primary',
            'type'    => 'submit',
          ]); ?>
        </div>
        <?= form_close(); ?>
      <?php endif; ?>
    <?php else: ?>
      <p class="ipb-ticket-composer-note is-danger">User not found.</p>
    <?php endif; ?>
  </div>
</div>
