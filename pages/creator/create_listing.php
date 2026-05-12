<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/seller_guard.php';
require_once __DIR__ . '/../../includes/upload_helper.php';

$pageTitle = 'Create Listing';
$dbc    = getDB();
$userID = $_SESSION['user_id'];

// Fetch categories for dropdown
$categories = $dbc->query("SELECT CategoryID, CategoryName FROM pm_categories ORDER BY CategoryName")
                  ->fetch_all(MYSQLI_ASSOC);

$errors   = [];
$formData = ['title' => '', 'description' => '', 'price' => '', 'category_id' => '', 'status' => 'draft'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price']       ?? '';
    $categoryID  = $_POST['category_id'] ?? '';
    $status      = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';

    $formData = compact('title', 'description', 'price', 'categoryID', 'status');

    // Server-side validation
    if (strlen($title) < 3)        $errors[] = 'Title must be at least 3 characters.';
    if (strlen($description) < 10) $errors[] = 'Description must be at least 10 characters.';
    if (!is_numeric($price) || $price < 0) $errors[] = 'Price must be a valid non-negative number.';
    if (empty($categoryID))        $errors[] = 'Please select a category.';

    // Verify category exists
    if (!empty($categoryID)) {
        $chk = $dbc->prepare("SELECT CategoryID FROM pm_categories WHERE CategoryID = ?");
        $chk->bind_param('s', $categoryID);
        $chk->execute();
        if ($chk->get_result()->num_rows === 0) $errors[] = 'Invalid category selected.';
        $chk->close();
    }

    // Image upload — required
    $imageURL = '';
    if (empty($_FILES['image']['name'])) {
        $errors[] = 'Please upload a listing image.';
    } else {
        $img = handleUpload(
            $_FILES['image'], 'images',
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            5 * 1024 * 1024
        );
        if ($img['error']) {
            $errors[] = 'Image: ' . $img['error'];
        } else {
            $imageURL = $img['path'];
        }
    }

    // Media upload — optional
    $mediaURL = '';
    if (!empty($_FILES['media']['name'])) {
        $med = handleUpload(
            $_FILES['media'], 'media',
            ['video/mp4', 'audio/mpeg', 'audio/wav', 'audio/ogg', 'video/webm'],
            50 * 1024 * 1024
        );
        if ($med['error']) {
            $errors[] = 'Media: ' . $med['error'];
        } else {
            $mediaURL = $med['path'];
        }
    }

    if (empty($errors)) {
        $stmt = $dbc->prepare(
            "INSERT INTO pm_listings
                (ListingID, Title, Description, Price, ImageURL, MediaURL, Status, CreatedAt, UserID, CategoryID)
             VALUES (UUID(), ?, ?, ?, ?, ?, ?, NOW(), ?, ?)"
        );
        $stmt->bind_param('ssdsssss',
            $title, $description, $price, $imageURL, $mediaURL, $status, $userID, $categoryID
        );

        if ($stmt->execute()) {
            $stmt->close();
            mysqli_close($dbc);
            header('Location: my_listings.php?success=' . urlencode('Listing created successfully!'));
            exit;
        }
        $errors[] = 'Could not save the listing. Please try again.';
        $stmt->close();
    }
}

mysqli_close($dbc);
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>

<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active text-white">Create Listing</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create New Listing</h2>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" id="errorBox">
                    <strong><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card card-pm">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" enctype="multipart/form-data" id="createForm" novalidate>

                        <!-- Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label fw-semibold">
                                Listing Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg"
                                   id="title" name="title"
                                   value="<?= htmlspecialchars($formData['title']) ?>"
                                   placeholder="Enter a clear, descriptive title"
                                   maxlength="200" required>
                            <div class="form-text">Minimum 3 characters.</div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">
                                Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="5" maxlength="2000" required
                                      placeholder="Describe your listing in detail..."><?= htmlspecialchars($formData['description']) ?></textarea>
                            <div class="d-flex justify-content-between">
                                <div class="form-text">Minimum 10 characters.</div>
                                <div class="form-text" id="descCount">0 / 2000</div>
                            </div>
                        </div>

                        <!-- Price & Category -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="price" class="form-label fw-semibold">
                                    Price <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">BD</span>
                                    <input type="number" class="form-control"
                                           id="price" name="price"
                                           value="<?= htmlspecialchars($formData['price']) ?>"
                                           placeholder="0.000" min="0" step="0.001" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label fw-semibold">
                                    Category <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select a category…</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['CategoryID']) ?>"
                                            <?= $formData['category_id'] === $cat['CategoryID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['CategoryName']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Image upload -->
                        <div class="mb-4">
                            <label for="image" class="form-label fw-semibold">
                                Listing Image <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="image" name="image"
                                   accept="image/jpeg,image/png,image/gif,image/webp" required>
                            <div class="form-text">JPG, PNG, GIF, or WEBP — max 5 MB.</div>
                            <div id="imagePreview" class="mt-2 d-none">
                                <img id="previewImg" src="#" alt="Preview"
                                     class="rounded" style="max-height:200px;max-width:100%;object-fit:cover;">
                            </div>
                        </div>

                        <!-- Media upload -->
                        <div class="mb-4">
                            <label for="media" class="form-label fw-semibold">
                                Media File <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <input type="file" class="form-control" id="media" name="media"
                                   accept="video/mp4,audio/mpeg,audio/wav,audio/ogg,video/webm">
                            <div class="form-text">MP4, MP3, WAV, OGG, or WEBM — max 50 MB.</div>
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Publish Status</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                           name="status" id="statusDraft" value="draft"
                                           <?= $formData['status'] !== 'published' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="statusDraft">
                                        <i class="bi bi-pencil-square me-1"></i>Save as Draft
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                           name="status" id="statusPublished" value="published"
                                           <?= $formData['status'] === 'published' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="statusPublished">
                                        <i class="bi bi-eye me-1"></i>Publish Now
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-accent btn-lg px-5">
                                <i class="bi bi-check-circle me-2"></i>Create Listing
                            </button>
                            <a href="my_listings.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Description character counter
const descEl    = document.getElementById('description');
const countEl   = document.getElementById('descCount');
const syncCount = () => countEl.textContent = descEl.value.length + ' / 2000';
descEl.addEventListener('input', syncCount);
syncCount();

// Image preview
document.getElementById('image').addEventListener('change', function () {
    const preview = document.getElementById('imagePreview');
    const img     = document.getElementById('previewImg');
    if (this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; preview.classList.remove('d-none'); };
        reader.readAsDataURL(this.files[0]);
    } else {
        preview.classList.add('d-none');
    }
});

// Client-side validation (defence-in-depth — server also validates)
document.getElementById('createForm').addEventListener('submit', function (e) {
    const title  = document.getElementById('title').value.trim();
    const desc   = document.getElementById('description').value.trim();
    const price  = parseFloat(document.getElementById('price').value);
    const cat    = document.getElementById('category_id').value;
    const image  = document.getElementById('image').files[0];
    const media  = document.getElementById('media').files[0];

    const errs = [];
    if (title.length < 3)        errs.push('Title must be at least 3 characters.');
    if (desc.length < 10)        errs.push('Description must be at least 10 characters.');
    if (isNaN(price) || price < 0) errs.push('Price must be a valid non-negative number.');
    if (!cat)                    errs.push('Please select a category.');
    if (!image)                  errs.push('Please upload a listing image.');

    const imgTypes   = ['image/jpeg','image/png','image/gif','image/webp'];
    const mediaTypes = ['video/mp4','audio/mpeg','audio/wav','audio/ogg','video/webm'];

    if (image && !imgTypes.includes(image.type))   errs.push('Image must be JPG, PNG, GIF, or WEBP.');
    if (image && image.size > 5 * 1024 * 1024)     errs.push('Image must be under 5 MB.');
    if (media && !mediaTypes.includes(media.type)) errs.push('Media must be MP4, MP3, WAV, OGG, or WEBM.');
    if (media && media.size > 50 * 1024 * 1024)    errs.push('Media file must be under 50 MB.');

    if (errs.length) {
        e.preventDefault();
        let box = document.getElementById('errorBox');
        if (!box) {
            box = document.createElement('div');
            box.id = 'errorBox';
            box.className = 'alert alert-danger';
            document.getElementById('createForm').before(box);
        }
        box.innerHTML = '<strong><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following:</strong>'
            + '<ul class="mb-0 mt-2"><li>' + errs.join('</li><li>') + '</li></ul>';
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
