<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list">

        <?= $this->include('components/page-header', [
          'title' => $title ?? 'File Manager',
          'subtitle' => 'Manage your files and folders',
          'breadcrumb' => [
            ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
            ['label' => $title ?? 'File Manager'],
          ],
        ]); ?>

        <div class="box box-warning">
            <div class="box-header with-border ipb-box-toolbar">
                <div class="ipb-list-toolbar">
                  <div class="ipb-list-toolbar-filters">
                    <span class="ipb-filter-label"><i class="fa fa-folder" aria-hidden="true"></i> Root: <?= esc($root); ?></span>
                  </div>
                  <div class="ipb-list-toolbar-actions">
                        <button type="button" class="btn btn-default" onclick="location.reload()">
                            <i class="fa fa-refresh" aria-hidden="true"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createFolderModal">
                            <i class="fa fa-folder-plus" aria-hidden="true"></i> New Folder
                        </button>
                  </div>
                </div>
            </div>
            
            <div class="box-body">
                <!-- Breadcrumbs Path -->
                <div class="well well-sm" style="background: var(--surface); border-left: 3px solid #f39c12;">
                    <i class="fa fa-home"></i> 
                    <a href="<?= base_url('file-manager'); ?>">root</a>
                    <?php 
                    $pathParts = explode(DIRECTORY_SEPARATOR, $currentPath);
                    $builtPath = '';
                    foreach ($pathParts as $part): 
                        if ($part === '') continue;
                        $builtPath .= ($builtPath ? DIRECTORY_SEPARATOR : '') . $part;
                    ?>
                        <span class="text-muted"> / </span>
                        <a href="<?= base_url('file-manager?path=' . urlencode($builtPath)); ?>"><?= $part; ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="fileTable">
                        <caption class="sr-only">File and folder list</caption>
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Size</th>
                                <th scope="col">Last Modified</th>
                                <th scope="col" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($currentPath !== ''): ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('file-manager?path=' . urlencode(dirname($currentPath) === '.' ? '' : dirname($currentPath))); ?>" style="color: var(--text-secondary, #555);">
                                            <i class="fa fa-level-up-alt"></i> ..
                                        </a>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($items as $item): 
                                if ($item['name'] === '..' || $item['name'] === '.') continue;
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($item['is_dir']): ?>
                                            <a href="<?= base_url('file-manager?path=' . urlencode($item['path'])); ?>" style="color: #f39c12; font-weight: bold;">
                                                <i class="fa fa-folder" style="margin-right: 5px;"></i> <?= $item['name']; ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--text-primary);">
                                                <i class="fa <?= fm_get_icon($item['ext']); ?>" style="margin-right: 5px; color: var(--text-muted);"></i> <?= $item['name']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $item['is_dir'] ? '--' : fm_format_size($item['size']); ?></td>
                                    <td class="small"><?= date('Y-m-d H:i:s', $item['mtime']); ?></td>
                                    <td class="text-right">
                                        <div class="btn-group">
                                            <?php if (!$item['is_dir']): ?>
                                                <button class="btn btn-xs btn-primary" onclick="editFile('<?= $item['path']; ?>')" data-toggle="tooltip" title="Edit" aria-label="Edit file">
                                                    <i class="fa fa-edit" aria-hidden="true"></i>
                                                </button>
                                                <a href="<?= base_url('file-manager/download?path=' . urlencode($item['path'])); ?>" class="btn btn-xs btn-success" data-toggle="tooltip" title="Download" aria-label="Download file">
                                                    <i class="fa fa-download" aria-hidden="true"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-xs btn-danger" onclick="deleteItem('<?= $item['path']; ?>', '<?= $item['name']; ?>')" data-toggle="tooltip" title="Delete" aria-label="Delete item">
                                                <i class="fa fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($items) && $currentPath === ''): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No files found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal for Creating Folder -->
<div class="modal fade" id="createFolderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-yellow">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Create New Folder</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Folder Name</label>
                    <input type="text" id="newFolderName" class="form-control" placeholder="Enter folder name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-yellow" onclick="createNewFolder()">Create</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Editing File -->
<div class="modal fade" id="editFileModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" style="width: 95%;" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="editFileTitle">Edit File</h4>
            </div>
            <div class="modal-body" style="padding: 0; position: relative;">
                <div id="fileEditor" style="height: 60vh; width: 100%;"></div>
                <input type="hidden" id="editFilePath">
            </div>
            <div class="modal-footer">
                <div class="pull-left">
                    <span id="editorStatus" class="text-muted small"></span>
                </div>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveFile()">
                    <i class="fa fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<?php
function fm_get_icon($ext) {
    $icons = [
        'php' => 'fa-file-code',
        'html' => 'fa-file-code',
        'css' => 'fa-file-code',
        'js' => 'fa-file-code',
        'json' => 'fa-file-code',
        'txt' => 'fa-file-text',
        'log' => 'fa-file-text',
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'gif' => 'fa-file-image',
        'zip' => 'fa-file-archive',
        'pdf' => 'fa-file-pdf',
    ];
    return $icons[strtolower($ext ?? '')] ?? 'fa-file';
}

function fm_format_size($bytes) {
    if ($bytes <= 0) return '0 B';
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $pow = floor(log($bytes) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>
<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.23.4/ace.js"></script>
<script>
    let editor = ace.edit("fileEditor");
    editor.setTheme("ace/theme/monokai");
    editor.setOptions({
        fontSize: "14px",
        showPrintMargin: false,
        showGutter: true,
        highlightActiveLine: true,
        wrap: true,
        useSoftTabs: true,
        tabSize: 4
    });

    $(function() {
        $('[data-toggle="tooltip"]').tooltip();
        
        // Handle shortcuts
        $(document).bind('keydown', function(e) {
            if(e.ctrlKey && (e.which == 83)) { // Ctrl + S
                if ($('#editFileModal').hasClass('in')) {
                    e.preventDefault();
                    saveFile();
                    return false;
                }
            }
        });
    });

    function editFile(path) {
        $('#editorStatus').text('Loading...');
        $.ajax({
            url: '<?= base_url('file-manager/view'); ?>',
            type: 'GET',
            data: { path: path },
            success: function(res) {
                if (res.status === 'success') {
                    $('#editFilePath').val(res.response.path);
                    $('#editFileTitle').html('<i class="fa fa-edit"></i> Editing: <span style="font-weight: normal;">' + res.response.name + '</span>');
                    
                    let mode = "ace/mode/text";
                    let ext = res.response.extension.toLowerCase();
                    if (ext === 'php') mode = "ace/mode/php";
                    else if (ext === 'js') mode = "ace/mode/javascript";
                    else if (ext === 'css') mode = "ace/mode/css";
                    else if (ext === 'html') mode = "ace/mode/html";
                    else if (ext === 'json') mode = "ace/mode/json";
                    else if (ext === 'sql') mode = "ace/mode/sql";
                    
                    editor.session.setMode(mode);
                    editor.setValue(res.response.content, -1);
                    $('#editFileModal').modal('show');
                    $('#editorStatus').text('Ready');
                } else {
                    tata.error("Couldn't load file", res.response || 'Could not load file');
                }
            },
            error: function() {
                tata.error("Couldn't load file", 'Server error while loading file');
            }
        });
    }

    function saveFile() {
        let path = $('#editFilePath').val();
        let content = editor.getValue();
        $('#editorStatus').text('Saving...');
        
        $.ajax({
            url: '<?= base_url('file-manager/save'); ?>',
            type: 'POST',
            data: {
                path: path,
                content: content,
                <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
            },
            success: function(res) {
                if (res.status === 'success') {
                    tata.success('File saved', res.response);
                    $('#editorStatus').text('Last saved at ' + new Date().toLocaleTimeString());
                } else {
                    tata.error("Couldn't save file", res.response || 'Failed to save');
                    $('#editorStatus').text('Save failed!');
                }
            },
            error: function() {
                tata.error("Couldn't save file", 'Server error while saving');
                $('#editorStatus').text('Save failed!');
            }
        });
    }

    function deleteItem(path, name) {
        swal({
            title: "Are you sure?",
            text: "You are about to delete: " + name + "\nThis action cannot be undone!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    url: '<?= base_url('file-manager/delete'); ?>',
                    type: 'POST',
                    data: {
                        path: path,
                        <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            tata.success('Deleted', res.response);
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            tata.error("Couldn't delete", res.response || 'Failed to delete');
                        }
                    },
                    error: function() {
                        tata.error("Couldn't delete", 'Server error while deleting');
                    }
                });
            }
        });
    }

    function createNewFolder() {
        let name = $('#newFolderName').val();
        if (!name) return tata.warn('Wait', 'Please enter a folder name');
        
        $.ajax({
            url: '<?= base_url('file-manager/create-folder'); ?>',
            type: 'POST',
            data: {
                parent: '<?= $currentPath; ?>',
                name: name,
                <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
            },
            success: function(res) {
                if (res.status === 'success') {
                    tata.success('Folder created', res.response);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    tata.error("Couldn't create folder", res.response || 'Failed to create folder');
                }
            },
            error: function() {
                tata.error("Couldn't create folder", 'Server error while creating folder');
            }
        });
    }
</script>
<?= $this->endSection(); ?>
