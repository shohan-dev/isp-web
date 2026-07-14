<style>
    .ipb-settings-servers {
        max-width: 100%;
        min-width: 0;
    }
    .ipb-settings-servers h2 {
        margin: 0 0 12px;
        font-size: 16px;
        font-weight: 800;
        color: var(--text-primary, #0f172a);
    }
    .ipb-settings-servers > .btn {
        margin-bottom: 12px;
    }
    .ipb-settings-servers #movieTableWrapper {
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
        min-width: 640px;
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
    #movieModal {
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
    #movieModal .modal-content {
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
    #movieModal label {
        display: block;
        margin: 10px 0 4px;
        font-weight: 700;
        font-size: 12.5px;
    }
    #movieModal input[type="text"],
    #movieModal input[type="number"],
    #movieModal textarea,
    #movieModal input[type="file"] {
        width: 100%;
        padding: 10px 12px;
        box-sizing: border-box;
        border: 1.5px solid var(--border, #e6eaf0);
        border-radius: 10px;
        min-height: 42px;
    }
    #previewImg {
        display: none;
        width: 120px;
        max-width: 100%;
        margin-top: 10px;
    }
    @media (max-width: 767px) {
        .ipb-settings-servers > .btn {
            width: 100%;
            min-height: 44px;
        }
        .ipb-settings-servers table {
            min-width: 560px;
            font-size: 13px;
        }
        #movieModal {
            padding: 0;
            align-items: stretch;
        }
        #movieModal .modal-content {
            max-width: none;
            width: 100%;
            min-height: 100%;
            border-radius: 0;
            max-height: none;
        }
        #movieModal .btn {
            min-height: 44px;
            width: 100%;
            margin: 6px 0;
        }
    }
</style>

<div class="ipb-settings-servers">
<h2>Movie Manager</h2>
<button type="button" class="btn btn-primary" onclick="openAddModal()">+ Add Movie</button>

<div id="movieTableWrapper">
    <div class="table-responsive">
    <table id="movieTable" class="table">
        <caption class="sr-only">Movie list</caption>
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Image</th>
                <th scope="col">Name</th>
                <th scope="col">URL</th>
                <th scope="col">Details</th>
                <th scope="col">Rating</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- Movie rows will appear here -->
        </tbody>
    </table>
    </div>
</div>

<!-- Modal -->
<div id="movieModal">
    <div class="modal-content">
        <h3>Movie Form</h3>
        <input type="hidden" id="movie_id">
        <label>Name</label>
        <input type="text" id="movie_name">

        <label>URL</label>
        <input type="text" id="movie_url">

        <label>Details</label>
        <textarea id="movie_details"></textarea>

        <label>Rating</label>
        <input type="number" id="movie_rating" step="0.1" min="0" max="5">

        <label>Image</label>
        <input type="file" id="movie_image">
        <img id="previewImg" src="">

        <div class="ipb-settings-servers-actions" style="margin-top: 10px; display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end;">
            <button type="button" class="btn btn-success" onclick="saveMovie()">Save</button>
            <button type="button" class="btn btn-warning" onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>
</div>

<script>
    var userId = userId || '<?= getSession('user_id') ?>';
    var baseUrl = '<?= rtrim(base_url(), '/') ?>';

    /* -------------------------
   LOAD ALL MOVIES
------------------------- */

    function loadMovies() {
        let tbody = document.querySelector("#movieTable tbody");
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">'
            + '<span class="ipb-spinner ipb-spinner--sm"></span> Loading…</td></tr>';

        fetch(`${baseUrl}/api/movieservers/?user_id=${userId}`)
            .then(res => res.json())
            .then(response => {
                console.log("Movies Data from Server:", response);

                // Access the array inside the 'data' property
                const movies = response.data.data;

                if (!movies || !Array.isArray(movies)) {
                    console.error("Movies data is not an array or missing!");
                    throw new Error("bad payload");
                }

                let tbody = document.querySelector("#movieTable tbody");

                if (!movies.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No movies yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = "";

                movies.forEach(movie => {
                    let imgHtml = movie.image ?
                        `<img src="${baseUrl}/assets/movies/${movie.image}" style="width:60px;">` :
                        '';

                    let row = `
                    <tr>
                        <td>${movie.id}</td>
                        <td>${imgHtml}</td>
                        <td>${movie.name}</td>
                        <td>${movie.url}</td>
                        <td>${movie.details}</td>
                        <td>${movie.rating}</td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="editMovie(${movie.id})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteMovie(${movie.id})">Delete</button>
                        </td>
                    </tr>
                `;
                    tbody.innerHTML += row;
                });
            })
            .catch(err => {
                console.error("Failed to load movies:", err);
                let tbody = document.querySelector("#movieTable tbody");
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">'
                    + '<span style="color:var(--error-600)">Could not load.</span> '
                    + '<button type="button" class="btn btn-xs btn-default" onclick="loadMovies()">Retry</button></td></tr>';
            });
    }

    // Call this function initially
    loadMovies();



    function openAddModal() {
        document.getElementById("movie_id").value = "";
        document.getElementById("movie_name").value = "";
        document.getElementById("movie_url").value = "";
        document.getElementById("movie_details").value = "";
        document.getElementById("movie_rating").value = "";
        document.getElementById("movie_image").value = "";
        document.getElementById("previewImg").style.display = "none";
        document.getElementById("movieModal").style.display = "flex";
    }

    // ---------------------
    // Close Modal
    // ---------------------
    function closeModal() {
        document.getElementById("movieModal").style.display = "none";
    }

    /* -------------------------
       SAVE MOVIE (Add or Update)
    ------------------------- */
    function saveMovie() {
        let id = document.getElementById("movie_id").value;

        let formData = new FormData();
        formData.append("name", document.getElementById("movie_name").value);
        formData.append("url", document.getElementById("movie_url").value);
        formData.append("details", document.getElementById("movie_details").value);
        formData.append("rating", document.getElementById("movie_rating").value);
        formData.append("admin_id", userId);

        // Add image if selected
        const fileInput = document.getElementById("movie_image");
        if (fileInput.files.length > 0) {
            formData.append("image", fileInput.files[0]);
        }

        // Debug
        console.log("Sending Movie Data:");
        for (let pair of formData.entries()) {
            console.log(pair[0] + " : " + pair[1]);
        }

        let url = id ?
            `${baseUrl}/api/movieservers/update/${id}` :
            `${baseUrl}/api/movieservers/add`;

        fetch(url, {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                console.log("Server Response:", result);
                alert(result.data && result.data.message ? result.data.message : (result.message || "Saved successfully"));
                loadMovies();
                resetForm();
                closeModal();
            })
            .catch(err => {
                console.error("Error saving movie:", err);
                // alert("Failed to save movie.");
            });
    }

    /* -------------------------
       EDIT MOVIE 
    ------------------------- */
    function editMovie(id) {
        fetch(`${baseUrl}/api/movieservers/view/${id}`)
            .then(res => res.json())
            .then(movie => {
                console.log("Movies Data from Server:", movie);

                // Ensure we have a single object
                const m = Array.isArray(movie.data) ? movie.data[0] : movie.data;

                document.getElementById("movie_id").value = m.id;
                document.getElementById("movie_name").value = m.name;
                document.getElementById("movie_url").value = m.url;
                document.getElementById("movie_details").value = m.details;
                document.getElementById("movie_rating").value = m.rating;

                const preview = document.getElementById("previewImg");
                if (m.image) {
                    preview.src = `${baseUrl}/assets/movies/${m.image}`;
                    preview.style.display = "block";
                } else {
                    preview.style.display = "none";
                }

                // Open modal
                document.getElementById("movieModal").style.display = "flex";
            })
            .catch(err => console.error(err));
    }




    /* -------------------------
       DELETE MOVIE
    ------------------------- */
    function deleteMovie(id) {
        if (!confirm("Delete this movie?")) return;

        fetch(`${baseUrl}/api/movieservers/delete/${id}`)
            .then(res => res.json())
            .then(result => {
                alert(result.data && result.data.message ? result.data.message : (result.message || "Deleted successfully"));
                loadMovies();
            });
    }

    /* -------------------------
       RESET FORM
    ------------------------- */
    function resetForm() {
        document.getElementById("movie_id").value = "";
        document.getElementById("movie_name").value = "";
        document.getElementById("movie_url").value = "";
        document.getElementById("movie_details").value = "";
        document.getElementById("movie_rating").value = "";
        document.getElementById("movie_image").value = "";
        document.getElementById("previewImg").style.display = "none";
    }

    /* -------------------------
       IMAGE PREVIEW
    ------------------------- */
    document.getElementById("movie_image").addEventListener("change", function() {
        const preview = document.getElementById("previewImg");
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
    loadMovies();
</script>