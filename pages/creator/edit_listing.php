<?php
// Prevent session conflict notifications if a session block is already active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and guard files using clean relative paths
include_once "../../config/db.php";
include_once "../../includes/seller_guard.php";
include_once "../../includes/upload_helper.php";

$pageTitle  = 'Edit Listing';
$dbc        = getDB();
$userID     = $_SESSION['user_id'];
$listingID  = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($listingID)) {
    header('Location: my_listings.php?error=' . urlencode('No listing specified.'));
    exit;
}

// Escape data input to secure the structural search query
$safeListingID = mysqli_real_escape_string($dbc, $listingID);

// Verify record ownership and pull the matching listing information
$sql = "SELECT * FROM pm_listings WHERE ListingID = '$safeListingID' AND UserID = '$userID'";
$result = mysqli_query($dbc, $sql);
$listing = mysqli_fetch_assoc($result);

if (!$listing) {
    mysqli_close($dbc);
    header('Location: my_listings.php?error=' . urlencode('Listing not found or access denied.'));
    exit;
}

// Fetch categories from the database to build out the template menu options
$categories = [];
$catResult = mysqli_query($dbc, "SELECT CategoryID, CategoryName FROM pm_categories ORDER BY CategoryName");
if ($catResult) {
    while ($row = mysqli_fetch_assoc($catResult)) {
        $categories[] = $row;
    }
}

// Map the baseline record data values straight to clear variable sets
$errors      = [];
$title       = $listing['Title'];
$description = $listing['Description'];
$price       = $listing['Price'];
$categoryID  = $listing['CategoryID'];
$status      = $listing['Status'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price'] ?? '';
    $categoryID  = $_POST['category_id'] ?? '';
    $status      = (isset($_POST['status']) && $_POST['status'] == 'published') ? 'published' : 'draft';

    // Server-side length string validations
    if (strlen($title) < 3) {
        $errors[] = 'Title must be at least 3 characters.';
    }
    if (strlen($description) < 10) {
        $errors[] = 'Description must be at least 10 characters.';
    }
    if (!is_numeric($price) || $price < 0) {
        $errors[] = 'Price must be a valid non-negative number.';
    }
    if (empty($categoryID)) {
        $errors[] = 'Please select a category.';
    }

    if (!empty($categoryID)) {
        $safeCatID = mysqli_real_escape_string($dbc, $categoryID);
        $chkQuery = "SELECT CategoryID FROM pm_categories WHERE CategoryID = '$safeCatID'";
        $chkResult = mysqli_query($dbc, $chkQuery);
        if (mysqli_num_rows($chkResult) == 0) {
            $errors[] = 'Invalid category selected.';
        }
    }

    // Image upload loop - fallback on the database string if file input is empty
    $imageURL = $listing['ImageURL'];
    if (!empty($_FILES['image']['name'])) {
        $img = handleUpload(
            $_FILES['image'],
            'images',
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            5 * 1024 * 1024
        );
        if ($img['error']) {
            $errors[] = 'Image: ' . $img['error'];
        } else {
            $imageURL = $img['path'];
        }
    }

    // Media upload loop - fallback on the database string if file input is empty
    $mediaURL = $listing['MediaURL'];
    if (!empty($_FILES['media']['name'])) {
        $med = handleUpload(
            $_FILES['media'],
            'media',
            ['video/mp4', 'audio/mpeg', 'audio/wav', 'audio/ogg', 'video/webm'],
            50 * 1024 * 1024
        );
        if ($med['error']) {
            $errors[] = 'Media: ' . $med['error'];
        } else {
            $mediaURL = $med['path'];
        }
    }

    // Save alterations back to the directory table rows if errors array is clean
    if (empty($errors)) {
        $safeTitle = mysqli_real_escape_string($dbc, $title);
        $safeDesc  = mysqli_real_escape_string($dbc, $description);

        $updateSql = "UPDATE pm_listings 
                      SET Title = '$safeTitle', 
                          Description = '$safeDesc', 
                          Price = '$price', 
                          ImageURL = '$imageURL', 
                          MediaURL = '$mediaURL', 
                          Status = '$status', 
                          CategoryID = '$categoryID'
                      WHERE ListingID = '$safeListingID' AND UserID = '$userID'";

        $updateResult = mysqli_query($dbc, $updateSql);

        if ($updateResult) {
            mysqli_close($dbc);
            header('Location: my_listings.php?success=' . urlencode('Listing updated successfully!'));
            exit;
        }
        $errors[] = 'Could not update the listing. Please try again.';
    }
}

mysqli_close($dbc);
?>

<?php include "../../includes/header.php"; ?>
<?php include "../../includes/navbar.php"; ?>

<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="my_listings.php">My Listings</a></li>
                <li class="breadcrumb-item active text-white">Edit</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit Listing</h2>
    </div>
</div>

<div class="container page-content pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" id="errorBox">
                    <strong><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card card-pm">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" enctype="multipart/form-data" id="editForm">

                        <div class="mb-4">
                            <label for="title" class="form-label fw-semibold">
                                Listing Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg"
                                id="title" name="title"
                                value="<?php echo htmlspecialchars($title); ?>"
                                maxlength="200" required>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">
                                Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="description" name="description"
                                rows="5" maxlength="2000" required><?php echo htmlspecialchars($description); ?></textarea>
                            <div class="d-flex justify-content-end">
                                <div class="form-text" id="descCount">0 / 2000</div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="price" class="form-label fw-semibold">
                                    Price <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">BD</span>
                                    <input type="number" class="form-control"
                                        id="price" name="price"
                                        value="<?php echo htmlspecialchars($price); ?>"
                                        min="0" step="0.001" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label fw-semibold">
                                    Category <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select a category…</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['CategoryID']; ?>"
                                            <?php if ($categoryID == $cat['CategoryID']) {
                                                echo 'selected';
                                            } ?>>
                                            <?php echo htmlspecialchars($cat['CategoryName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Listing Image</label>
                            <?php if ($listing['ImageURL'] != ''): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars('../../' . $listing['ImageURL']); ?>"
                                        alt="Current image" class="rounded"
                                        style="max-height:160px; object-fit:cover;">
                                </div>
                            <?php endif; ?>
                            <label for="image" class="form-label">
                                Replace Image <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <input type="file" class="form-control" id="image" name="image"
                                accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-text">Leave empty to keep the current image. JPG, PNG, GIF, WEBP — max 5 MB.</div>
                            <div id="imagePreview" class="mt-2 d-none">
                                <img id="previewImg" src="#" alt="New image preview"
                                    class="rounded" style="max-height:160px; object-fit:cover;">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Media File</label>
                            <?php if ($listing['MediaURL'] != ''): ?>
                                <p class="text-muted small mb-1">
                                    <i class="bi bi-file-earmark-play me-1"></i>
                                    Current: <?php echo htmlspecialchars(basename($listing['MediaURL'])); ?>
                                </p>
                            <?php endif; ?>
                            <label for="media" class="form-label">
                                Replace Media <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <input type="file" class="form-control" id="media" name="media"
                                accept="video/mp4,audio/mpeg,audio/wav,audio/ogg,video/webm">
                            <div class="form-text">Leave empty to keep the current file. MP4, MP3, WAV, OGG, WEBM — max 50 MB.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Publish Status</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                        name="status" id="statusDraft" value="draft"
                                        <?php if ($status != 'published') {
                                            echo 'checked';
                                        } ?>>
                                    <label class="form-check-label" for="statusDraft">
                                        <i class="bi bi-pencil-square me-1"></i>Draft
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                        name="status" id="statusPublished" value="published"
                                        <?php if ($status == 'published') {
                                            echo 'checked';
                                        } ?>>
                                    <label class="form-check-label" for="statusPublished">
                                        <i class="bi bi-eye me-1"></i>Published
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-accent btn-lg px-5">
                                <i class="bi bi-check-circle me-2"></i>Save Changes
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
    // Logic parameters to sync textbox word counts
    const descTextArea = document.getElementById('description');
    const textCountLabel = document.getElementById('descCount');

    function runCountUpdate() {
        textCountLabel.textContent = descTextArea.value.length + ' / 2000';
    }
    descTextArea.addEventListener('input', runCountUpdate);
    runCountUpdate();

    // Generate canvas image visual frames on changes
    document.getElementById('image').addEventListener('change', function() {
        const previewBox = document.getElementById('imagePreview');
        const visualElement = document.getElementById('previewImg');
        if (this.files[0]) {
            const fileReader = new FileReader();
            fileReader.onload = function(e) {
                visualElement.src = e.target.result;
                previewBox.classList.remove('d-none');
            };
            fileReader.readAsDataURL(this.files[0]);
        } else {
            previewBox.classList.add('d-none');
        }
    });

    // Front-end dynamic client validation filters
    document.getElementById('editForm').addEventListener('submit', function(e) {
        const textTitle = document.getElementById('title').value.trim();
        const textDesc = document.getElementById('description').value.trim();
        const numericPrice = parseFloat(document.getElementById('price').value);
        const chosenCategory = document.getElementById('category_id').value;
        const fileImage = document.getElementById('image').files[0];
        const fileMedia = document.getElementById('media').files[0];

        const clientErrors = [];
        if (textTitle.length < 3) {
            clientErrors.push('Title must be at least 3 characters.');
        }
        if (textDesc.length < 10) {
            clientErrors.push('Description must be at least 10 characters.');
        }
        if (isNaN(numericPrice) || numericPrice < 0) {
            clientErrors.push('Price must be a valid non-negative number.');
        }
        if (!chosenCategory) {
            clientErrors.push('Please select a category.');
        }

        const standardImgTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        const standardMediaTypes = ['video/mp4', 'audio/mpeg', 'audio/wav', 'audio/ogg', 'video/webm'];

        if (fileImage && !standardImgTypes.includes(fileImage.type)) {
            clientErrors.push('Image must be JPG, PNG, GIF, or WEBP.');
        }
        if (fileImage && fileImage.size > 5 * 1024 * 1024) {
            clientErrors.push('Image must be under 5 MB.');
        }
        if (fileMedia && !standardMediaTypes.includes(fileMedia.type)) {
            clientErrors.push('Media must be MP4, MP3, WAV, OGG, or WEBM.');
        }
        if (fileMedia && fileMedia.size > 50 * 1024 * 1024) {
            clientErrors.push('Media file must be under 50 MB.');
        }

        // Stop post submission routines if validation tracker captures data elements
        if (clientErrors.length > 0) {
            e.preventDefault();
            let errorContainer = document.getElementById('errorBox');
            if (!errorContainer) {
                errorContainer = document.createElement('div');
                errorContainer.id = 'errorBox';
                errorContainer.className = 'alert alert-danger';
                document.getElementById('editForm').before(errorContainer);
            }
            errorContainer.innerHTML = '<strong><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following:</strong>' +
                '<ul class="mb-0 mt-2"><li>' + clientErrors.join('</li><li>') + '</li></ul>';
            errorContainer.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    });
</script>

<?php include "../../includes/footer.php"; ?>