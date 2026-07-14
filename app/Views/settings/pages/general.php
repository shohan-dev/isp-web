<div class="form-group">
    <label>Application Name</label>
    <?= form_input([
        'name'  => 'app_name',
        'class' => 'form-control',
        'value' => getSetting('app_name')
    ]); ?>
    <small id="app_name-error" class="error text-danger"></small>
</div>

<div class="form-group">
    <label>Application Subtitle</label>
    <?= form_input([
        'name'  => 'app_slogan',
        'class' => 'form-control',
        'value' => getSetting('app_slogan')
    ]); ?>
    <small id="app_slogan-error" class="error text-danger"></small>
</div>

<div class="form-group">
    <label>Application Logo</label>
    <?= form_input([
        'type'  => 'file',
        'name'  => 'app_logo',
        'class' => 'form-control',
        'accept' => 'image/png'
    ]); ?>
    <small id="app_logo-error" class="error text-danger"></small>

    <div style="margin: 10px 0">
        <?php $currentLogo = brandLogoUrlWithFallback(getBrandLogoUrl()); ?>
        <img src="<?= esc($currentLogo, 'attr'); ?>" class="img-responsive" style="max-width: 180px; max-height: 64px; object-fit: contain;" alt="Current application logo" />
        <small>Current Logo</small>
    </div>
</div>

<div class="form-group">
    <label>Application icon</label>
    <?= form_input([
        'type'  => 'file',
        'name'  => 'app_icon',
        'class' => 'form-control',
        'accept' => 'image/png'
    ]); ?>
    <small id="app_icon-error" class="error text-danger"></small>
    <div style="margin: 10px 0">
        <?php
        $iconFile = getSetting('app_icon', '', platformBrandingUserId());
        $currentIcon = $iconFile !== ''
            ? brandAssetUrl('img/logo/' . ltrim($iconFile, '/'))
            : getBrandFaviconUrl();
        ?>
        <img src="<?= esc($currentIcon, 'attr'); ?>" class="img-responsive" style="max-width: 68px; object-fit: contain;" alt="Current application icon" />
        <small>Current Icon</small>
    </div>
</div>