<style>
    .ipb-settings-servers { max-width: 100%; min-width: 0; }
    .ipb-settings-servers h2 {
        margin: 0 0 12px;
        font-size: 16px;
        font-weight: 800;
        color: var(--text-primary, #0f172a);
    }
    .ipb-settings-servers > .btn { margin-bottom: 12px; }
    .ipb-settings-servers #newsTableWrapper {
        overflow: auto;
        max-height: min(60vh, 500px);
        border: 1px solid var(--border, #e6eaf0);
        border-radius: 10px;
        -webkit-overflow-scrolling: touch;
        max-width: 100%;
    }
    .ipb-settings-servers table {
        width: 100%;
        border-collapse: collapse;
        min-width: 560px;
    }
    .ipb-settings-servers table th,
    .ipb-settings-servers table td {
        border-bottom: 1px solid var(--border, #e6eaf0);
        padding: 10px 12px;
        text-align: left;
        vertical-align: middle;
    }
    .ipb-settings-servers table th {
        background: var(--surface-2, #f8fafc);
        color: var(--text-muted, #94a3b8);
        position: sticky;
        top: 0;
        z-index: 1;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 700;
    }
    #newsModal {
        display: none;
        position: fixed;
        z-index: var(--z-modal, 1050);
        inset: 0;
        background-color: rgba(15, 23, 42, 0.5);
        justify-content: center;
        align-items: center;
        padding: 12px;
        box-sizing: border-box;
    }
    #newsModal .modal-content {
        background: var(--surface, #fff);
        color: var(--text-primary, #0f172a);
        padding: 20px;
        border-radius: 12px;
        max-width: 500px;
        width: 100%;
        max-height: min(90vh, 640px);
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        box-sizing: border-box;
    }
    #newsModal label {
        display: block;
        margin: 10px 0 4px;
        font-weight: 700;
        font-size: 12.5px;
    }
    #newsModal input[type="text"],
    #newsModal input[type="number"],
    #newsModal textarea,
    #newsModal input[type="file"] {
        width: 100%;
        padding: 10px 12px;
        box-sizing: border-box;
        border: 1.5px solid var(--border, #e6eaf0);
        border-radius: 10px;
        min-height: 42px;
    }
    #newsPreviewImg {
        display: none;
        width: 120px;
        max-width: 100%;
        margin-top: 10px;
    }
    @media (max-width: 767px) {
        .ipb-settings-servers > .btn { width: 100%; min-height: 44px; }
        .ipb-settings-servers table { min-width: 520px; font-size: 13px; }
        #newsModal { padding: 0; align-items: stretch; }
        #newsModal .modal-content {
            max-width: none;
            width: 100%;
            min-height: 100%;
            border-radius: 0;
            max-height: none;
        }
        #newsModal .btn { min-height: 44px; width: 100%; margin: 6px 0; }
    }
</style>

<div class="ipb-settings-servers">
<h2>News Manager</h2>
<button type="button" class="btn btn-primary" onclick="openNewsModal()">+ Add News</button>

<div id="newsTableWrapper">
    <div class="table-responsive">
    <table id="newsTable" class="table">
        <caption class="sr-only">News list</caption>
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Image</th>
                <th scope="col">Name</th>
                <th scope="col">URL</th>
                <th scope="col">Details</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- News rows will appear here -->
        </tbody>
    </table>
    </div>
</div>

<!-- Modal -->
<div id="newsModal">
    <div class="modal-content">
        <h3>News Form</h3>
        <input type="hidden" id="news_id">
        <label>Name</label>
        <input type="text" id="news_name" >

        <label>URL</label>
        <input type="text" id="news_url">

        <label>Details</label>
        <textarea id="news_details"></textarea>

        <label>Image</label>
        <input type="file" id="news_image">
        <img id="newsPreviewImg" src="">

        <div class="ipb-settings-servers-actions" style="margin-top: 10px; display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end;">
            <button type="button" class="btn btn-success" onclick="saveNews()">Save</button>
            <button type="button" class="btn btn-warning" onclick="closeNewsModal()">Cancel</button>
        </div>
    </div>
</div>
</div>

<script>
    var userId = userId || '<?= getSession('user_id') ?>';
    var baseUrl = '<?= rtrim(base_url(), '/') ?>';

    /* -------------------------
   LOAD ALL NEWS
 ------------------------- */
    function loadNews() {
        let tbody = document.querySelector("#newsTable tbody");
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">'
            + '<span class="ipb-spinner ipb-spinner--sm"></span> Loading…</td></tr>';

        fetch(`${baseUrl}/api/news/?user_id=${userId}`)
            .then(res => res.json())
            .then(response => {
                console.log("News Data from Server:", response);

                // Access the array inside the 'data' property
                const newss = response.data.data;

                if (!newss || !Array.isArray(newss)) {
                    console.error("News data is not an array or missing!");
                    throw new Error("bad payload");
                }

                let tbody = document.querySelector("#newsTable tbody");

                if (!newss.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No news yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = "";

                newss.forEach(news => {
                    let imgHtml = news.image ?
                        `<img src="${baseUrl}/assets/news/${news.image}" style="width:60px;">` :
                        '';

                    let row = `
                    <tr>
                        <td>${news.id}</td>
                        <td>${imgHtml}</td>
                        <td>${news.name}</td>
                        <td>${news.url}</td>
                        <td>${news.details}</td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="editNews(${news.id})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteNews(${news.id})">Delete</button>
                        </td>
                    </tr>
                `;
                    tbody.innerHTML += row;
                });
            })
            .catch(err => {
                console.error("Failed to load news:", err);
                let tbody = document.querySelector("#newsTable tbody");
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">'
                    + '<span style="color:var(--error-600)">Could not load.</span> '
                    + '<button type="button" class="btn btn-xs btn-default" onclick="loadNews()">Retry</button></td></tr>';
            });
    }

    // Call this function initially
    loadNews();



    function openNewsModal() {
        document.getElementById("news_id").value = "";
        document.getElementById("news_name").value = "";
        document.getElementById("news_url").value = "";
        document.getElementById("news_details").value = "";
        document.getElementById("news_image").value = "";
        document.getElementById("newsPreviewImg").style.display = "none";
        document.getElementById("newsModal").style.display = "flex";
    }

    // ---------------------
    // Close News Modal
    // ---------------------
    function closeNewsModal() {
        document.getElementById("newsModal").style.display = "none";
    }

    /* -------------------------
       SAVE NEWS (Add or Update)
    ------------------------- */
    function saveNews() {
        let id = document.getElementById("news_id").value;

        let formData = new FormData();
        formData.append("name", document.getElementById("news_name").value);
        formData.append("url", document.getElementById("news_url").value);
        formData.append("details", document.getElementById("news_details").value);
        formData.append("admin_id", userId);

        // Add image if selected
        const fileInput = document.getElementById("news_image");
        if (fileInput.files.length > 0) {
            formData.append("image", fileInput.files[0]);
        }

        // Debug
        console.log("Sending News Data:");
        for (let pair of formData.entries()) {
            console.log(pair[0] + " : " + pair[1]);
        }

        let url = id ?
            `${baseUrl}/api/news/update/${id}` :
            `${baseUrl}/api/news/add`;

        fetch(url, {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(results => {
                console.log("Server Response:", results);
                alert(results.message);
                loadNews();
                resetNewsForm();
                closeNewsModal();
            })
            .catch(err => {
                console.error("Error saving news:", err);
            });
    }



    /* -------------------------
       EDIT NEWS 
    ------------------------- */
    function editNews(id) {
        fetch(`${baseUrl}/api/news/view/${id}`)
            .then(res => res.json())
            .then(news => {
                console.log("News Data from Server:", news);

                // Ensure we have a single object
                const m = Array.isArray(news.data) ? news.data[0] : news.data;

                document.getElementById("news_id").value = m.id;
                document.getElementById("news_name").value = m.name;
                document.getElementById("news_url").value = m.url;
                document.getElementById("news_details").value = m.details;

                const preview = document.getElementById("newsPreviewImg");
                if (m.image) {
                    preview.src = `${baseUrl}/assets/news/${m.image}`;
                    preview.style.display = "block";
                } else {
                    preview.style.display = "none";
                }

                // Open modal
                document.getElementById("newsModal").style.display = "flex";
            })
            .catch(err => console.error(err));
    }




    /* -------------------------
       DELETE NEWS
    ------------------------- */
    function deleteNews(id) {
        if (!confirm("Delete this news?")) return;

        fetch(`${baseUrl}/api/news/delete/${id}`)
            .then(res => res.json())
            .then(results => {
                alert(results.message);
                loadNews();
            });
    }

    /* -------------------------
       RESET NEWS FORM
    ------------------------- */
    function resetNewsForm() {
        document.getElementById("news_id").value = "";
        document.getElementById("news_name").value = "";
        document.getElementById("news_url").value = "";
        document.getElementById("news_details").value = "";
        document.getElementById("news_image").value = "";
        document.getElementById("newsPreviewImg").style.display = "none";
    }

    /* -------------------------
       NEWS IMAGE PREVIEW
    ------------------------- */
    document.getElementById("news_image").addEventListener("change", function() {
        const preview = document.getElementById("newsPreviewImg");
        if (this.files && this.files[0]) {
            preview.src = URL.createObjectURL(this.files[0]);
            preview.style.display = "block";
        } else {
            preview.style.display = "none";
        }
    });

    /* -------------------------
       INITIAL LOAD
    ------------------------- */
    loadNews();
</script>