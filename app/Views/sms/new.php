<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

  <div class="content-wrapper">
    <section class="content ipb-saas-list">
      
    <?= $this->include('components/page-header', [
      'title' => 'New SMS',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'New SMS'],
      ],
    ]); ?>
<div class="box box-warning">
        <div class="box-header with-border">
          <h3 class="box-title">Create Personalized SMS</h3>
        </div>

        <?= form_open('', 'id="form"'); ?>

          <div class="box-body">

            <div class="form-group">
              <label>Service Area</label>
              <?php $data = empty($area) ? ['' => 'No area found!'] : ['' => '--Select--'] + array_combine(array_column($area, 'id'), array_map(fn($a) => "$a->area_name ($a->area_code)", $area)); ?>
              <?= form_dropdown('area', $data, '', 'class="form-control"'); ?>
            </div>

            <div class="form-group">
              <label>Customer <sup class="text-danger">*</sup></label>
              <select name="send_to[]" id="send_to" class="form-control" multiple="multiple" style="width:100%">
                <option value="all">All Customers</option>
              </select>
              <small id="send_to-error" class="error text-danger"></small>
              <small class="text-muted">Start typing a name or mobile number to search customers.</small>
            </div>

            <div class="form-group">
              <label>Templates</label>
              <?php $data = empty($template) ? ['' => 'No templates found!'] : ['' => '--Select--'] + array_column($template, 'template_name', 'id'); ?>
              <?= form_dropdown('tmp_id', $data, '', 'class="form-control" id="tmp_id"'); ?>
            </div>
            
            <div class="form-group">
              <label>SMS Content <sup class="text-danger">*</sup> (Global Template)</label>
              <?= form_textarea(['name' => 'content', 'class' => 'form-control', 'id' => 'sms_content', 'style' => 'height: 100px', 'maxlength' => 239]); ?>
              <div class="pull-right"><small id="counter"></small></div>
              <small id="content-error" class="error text-danger"></small>
            </div>

            <!-- ===== LIVE PREVIEW PANEL (EDITABLE) ===== -->
            <div id="preview_panel" style="display:none; margin-top:15px; border-top: 1px dashed #ccc; padding-top:15px;">
              <label><i class="fa fa-pencil"></i> Individual Message Preview <span class="badge bg-blue" id="preview_count">0</span></label>
              <p class="text-muted"><small>You can edit the messages directly below for specific customers before sending.</small></p>
              <div id="preview_cards" style="max-height:500px; overflow-y:auto; border:1px solid #eee; border-radius:4px; padding:10px; background:var(--surface, #fdfdfd);">
              </div>
            </div>

          </div>

          <div class="box-body">
            <?= form_button(["content" => "Send All Messages", "class" => "btn btn-lg btn-warning", "type" => "submit", "id" => "send_btn"]); ?>
            <button type="button" class="btn btn-default" id="refresh_preview_btn" style="display:none; margin-left:8px;">
              <i class="fa fa-refresh"></i> Reset Previews
            </button>
          </div>

        <?= form_close(); ?>
      </div>
    </section>
  </div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<!-- 08 §10 — duplicate Select2 CDN removed; main-layout.php already
     self-hosts select2.full.min.js and loads it before section('script'). -->

<style>
  #send_to + .select2-container {
    width: 100% !important;
  }

  #send_to + .select2-container .select2-selection--multiple {
    min-height: 44px;
    padding: 0;
  }

  #send_to + .select2-container .select2-selection__rendered {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 6px 8px !important;
    margin: 0 !important;
  }

  #send_to + .select2-container .select2-selection__choice {
    display: inline-flex !important;
    align-items: center !important;
    position: relative !important;
    margin: 0 !important;
    padding: 5px 12px 5px 30px !important;
    max-width: 100% !important;
    background: var(--info-50) !important;
    border: 1px solid var(--border-strong) !important;
    border-radius: 999px !important;
    color: var(--info-700) !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    line-height: 1.35 !important;
  }

  #send_to + .select2-container .select2-selection__choice__display {
    display: inline-block !important;
    max-width: 240px !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    padding: 0 !important;
    color: var(--info-700) !important;
  }

  #send_to + .select2-container .select2-selection__choice__remove {
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    bottom: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 26px !important;
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    border-right: 1px solid var(--border-strong) !important;
    border-radius: 999px 0 0 999px !important;
    background: var(--surface-2) !important;
    color: var(--info-600) !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
  }

  #send_to + .select2-container .select2-selection__choice__remove:hover {
    background: var(--surface-hover) !important;
    color: var(--info-700) !important;
  }

  #send_to + .select2-container .select2-search--inline .select2-search__field {
    margin: 0 !important;
    min-height: 28px !important;
    height: 28px !important;
    color: var(--text-primary) !important;
  }

  .sms-preview-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 12px;
    box-shadow: var(--shadow-1);
  }
  .sms-preview-card .preview-name {
    font-weight: 600;
    margin-bottom: 2px;
    color: var(--text-primary);
  }
  .sms-preview-card .preview-mobile {
    color: var(--text-muted);
    font-size: 11px;
    margin-bottom: 8px;
  }
  .editable-message {
    border: 1px solid var(--warning-500);
    background: var(--surface-2);
    padding: 8px;
    border-radius: 4px;
    font-size: 14px;
    min-height: 60px;
    outline: none;
    color: var(--text-primary);
    transition: all 0.2s;
  }
  .editable-message:focus {
    background: var(--surface);
    box-shadow: 0 0 5px color-mix(in srgb, var(--warning-500) 30%, transparent);
    border-color: var(--warning-600);
  }
  .char-warning {
    color: var(--warning-600);
    font-weight: bold;
  }
</style>

<script>
  const templatesData = <?= json_encode($template); ?>;
  let customerDetailsCache = {}; 
  let currentTemplateBody = '';
  let previewRequestCount = 0;

  // Recipient picker is remote/ajax-backed (bounded LIMIT server-side) instead of
  // inlining every active customer's <option> into the page HTML.
  $('#send_to').select2({
    placeholder: 'Select target customer(s)…',
    allowClear: true,
    width: '100%',
    closeOnSelect: false,
    minimumInputLength: 0,
    ajax: {
      url: '<?= route_to("route.sms.searchrecipients"); ?>',
      dataType: 'json',
      delay: 250,
      data: function(params) {
        return { q: params.term || '', area: $('select[name="area"]').val() };
      },
      processResults: function(data) {
        return { results: data.results || [] };
      },
      cache: true
    }
  });

  // Handle pre-selected customer IDs from URL — fetch their labels so Select2
  // (in ajax mode) has {id, text} Option objects to render before any search runs.
  const urlParams = new URLSearchParams(window.location.search);
  const preSelectedIds = urlParams.get('ids');
  if (preSelectedIds) {
    const idsArray = preSelectedIds.split(',').filter(id => id !== 'all');
    if (idsArray.length) {
      fetchCustomerDetails(idsArray, function() {
        idsArray.forEach(function(id) {
          const d = customerDetailsCache[id] || {};
          const label = (d.CustomerName || ('User #' + id)) + (d.Mobile ? ' (' + d.Mobile + ')' : '');
          if (!$('#send_to').find('option[value="' + id + '"]').length) {
            $('#send_to').append(new Option(label, id, true, true));
          }
        });
        $('#send_to').trigger('change');
      });
    }
  }

  function replacePlaceholders(text, data) {
    if (!text) return '';
    if (!data) return text;
    let out = text;
    for (const key in data) {
      const re = new RegExp(`\\{\\{${key}\\}\\}|\\{${key}\\}`, 'gi');
      out = out.replace(re, data[key]);
    }
    return out;
  }

  function fetchCustomerDetails(ids, callback) {
    const missing = ids.filter(id => id !== 'all' && !customerDetailsCache[id]);
    if (missing.length === 0) { callback(); return; }

    $.ajax({
      url: '<?= route_to("route.sms.getmulticustomerdetails"); ?>',
      type: 'POST',
      data: { ids: missing },
      headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
      success: function(res) {
        if (res.status === 'success') {
          $.each(res.response, function(id, d) { customerDetailsCache[id] = d; });
        }
        callback();
      },
      error: function() { callback(); }
    });
  }

  function updatePreview() {
    const tpl  = $('#sms_content').val();
    const ids  = $('#send_to').val() || [];
    const $panel = $('#preview_panel');
    const $cards = $('#preview_cards');
    
    if (!tpl || ids.length === 0 || ids.includes('all')) {
      $panel.hide();
      $('#refresh_preview_btn').hide();
      return;
    }

    const currentId = ++previewRequestCount;

    fetchCustomerDetails(ids, function() {
      if (currentId !== previewRequestCount) return;

      $cards.empty();
      $panel.show();
      $('#refresh_preview_btn').show();
      $('#preview_count').text(ids.length);

      ids.forEach(function(id) {
        if (id === 'all') return;
        const d = customerDetailsCache[id] || {};
        const msg = replacePlaceholders(tpl, d);

        $cards.append(`
          <div class="sms-preview-card" data-user-id="${id}">
            <div class="preview-name"><i class="fa fa-user"></i> ${d.CustomerName || 'User #'+id}</div>
            <div class="preview-mobile"><i class="fa fa-phone"></i> ${d.Mobile || ''}</div>
            <div class="editable-message" contenteditable="true">${msg}</div>
            <div class="preview-chars"><small><span class="count">${msg.length}</span> / 239 characters</small></div>
          </div>
        `);
      });
    });
  }

  // Update char count on individual edit
  $(document).on('keyup input', '.editable-message', function() {
    const len = $(this).text().length;
    const $counter = $(this).siblings('.preview-chars').find('.count');
    $counter.text(len);
    if (len > 239) $counter.addClass('char-warning');
    else $counter.removeClass('char-warning');
  });

  function syncTemplate() {
    const ids = $('#send_to').val() || [];
    if (ids.length === 1 && ids[0] !== 'all' && currentTemplateBody) {
      fetchCustomerDetails([ids[0]], function() {
        const d = customerDetailsCache[ids[0]] || {};
        $('#sms_content').val(replacePlaceholders(currentTemplateBody, d)).trigger('keyup');
      });
    } else {
      if (currentTemplateBody) $('#sms_content').val(currentTemplateBody);
      $('#sms_content').trigger('keyup');
    }
  }

  $('#tmp_id').change(function() {
    const tpl = templatesData.find(t => t.id == $(this).val());
    currentTemplateBody = tpl ? tpl.message_body : '';
    syncTemplate();
  });

  $('#send_to').on('change', function() {
    syncTemplate();
  });

  $('#sms_content').on('keyup input', function() {
    const limit = $(this).attr('maxlength');
    const len   = $(this).val().length;
    $('#counter').text(`${limit - len} characters left`);
    updatePreview();
  });

  $('#refresh_preview_btn').on('click', function() {
    updatePreview();
  });

  // Area filter is passed as part of every ajax search request (see select2 data()
  // above) — changing it just clears the current picks so stale-area selections
  // aren't sent, then lets the next search re-query scoped to the new area.
  $('select[name="area"]').change(function() {
    $('#send_to').val(null).trigger('change');
  });

  $('#form').submit(function(e) {
    e.preventDefault();
    const form = this;
    const fd   = new FormData(form);
    
    // Collect individual messages from preview cards
    $('#preview_cards .sms-preview-card').each(function() {
      const userId = $(this).data('user-id');
      const msg    = $(this).find('.editable-message').text();
      fd.append('custom_messages[' + userId + ']', msg);
    });

    $.ajax({
      url: '<?= route_to("route.sms.create"); ?>',
      type: 'POST',
      data: fd,
      contentType: false,
      cache: false,
      processData: false,
      beforeSend: function() {
        $(form).find('.error').html('');
        $(form).find('#send_btn').html("<i class='fa fa-spinner fa-spin'></i> Sending...").attr('disabled', true);
      },
      success: function(result) {
        tata.success('Messages sent', result.response);
        setTimeout(() => location.href = '<?= route_to("route.sms"); ?>', 1200);
      },
      error: function({responseText}) {
        $(form).find('#send_btn').html('Send All Messages').removeAttr('disabled');
        try {
          const result = JSON.parse(responseText);
          if (result.status === 'validation-error') {
            $.each(result.response, function(prefix, val) { $(form).find(`#${prefix}-error`).text(val); });
          } else { tata.error("Couldn't send messages", result.response); }
        } catch(e) { tata.error("Couldn't send messages", 'Server error'); }
      },
    });
  });
</script>

<?= $this->endSection('script'); ?>
