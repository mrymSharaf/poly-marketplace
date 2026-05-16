<?php
session_start();
// Include connection and utility scripts using standard relative paths
include "../../config/db.php";
include "../../includes/seller_guard.php";
include "../../includes/upload_helper.php";

$pageTitle = 'Create Listing';
$dbc = getDB();
$userID = $_SESSION['user_id'];

// Fetch available store categories to build the dropdown box
$categories = [];
$categoryResult = mysqli_query($dbc, "SELECT CategoryID, CategoryName FROM pm_categories ORDER BY CategoryName");
if ($categoryResult) {
    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $row;
    }
}

// Validation tracking flags
$errors = [];
$title = '';
$description = '';
$price = '';
$categoryID = '';
$status = 'draft';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $categoryID = $_POST['category_id'] ?? '';
    $status = ($_POST['status'] == 'published') ? 'published' : 'draft';

    // Server-side field verification
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

    // Verify selected category matching current database definitions
    if (!empty($categoryID)) {
        $checkCat = "SELECT CategoryID FROM pm_categories WHERE CategoryID = '$categoryID'";
        $checkResult = mysqli_query($dbc, $checkCat);
        if (mysqli_num_rows($checkResult) == 0) {
            $errors[] = 'Invalid category selected.';
        }
    }

    // Image uploading
    $imageURL = '';
    if (empty($_FILES['image']['name'])) {
        $errors[] = 'Please upload a listing image.';
    } else {
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

    // Media file uploading(Optional Field)
    $mediaURL = '';
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

    // Save records directly if validation array remains empty
    if (empty($errors)) {
        // Escaping raw text data inputs to defend database structure integrity
        $safeTitle = mysqli_real_escape_string($dbc, $title);
        $safeDesc = mysqli_real_escape_string($dbc, $description);

        $sql = "INSERT INTO pm_listings (Title, Description, Price, ImageURL, MediaURL, Status, UserID, CategoryID)
                VALUES ('$safeTitle', '$safeDesc', '$price', '$imageURL', '$mediaURL', '$status', '$userID', '$categoryID')";

        $runQuery = mysqli_query($dbc, $sql);

        if ($runQuery) {
            mysqli_close($dbc);
            header('Location: my_listings.php?success=' . urlencode('Listing created successfully!'));
            exit;
        } else {
            $errors[] = 'Could not save the listing. Please try again.';
        }
    }
}

mysqli_close($dbc);
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navbar.php'; ?>

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

            <div class="alert alert-danger" id="errorBox" <?php if (empty($errors)) {
                                                                echo 'style="display:none;"';
                                                            } ?>>
                <strong><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following:</strong>
                <ul class="mb-0 mt-2" id="errorList">
                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="card card-pm">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" enctype="multipart/form-data" id="createForm">

                        <div class="mb-4">
                            <label for="title" class="form-label fw-semibold">
                                Listing Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg"
                                id="title" name="title"
                                value="<?php echo htmlspecialchars($title); ?>"
                                placeholder="Enter a clear, descriptive title"
                                maxlength="200" required>
                            <div class="form-text">Minimum 3 characters.</div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">
                                Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="description" name="description"
                                rows="5" maxlength="2000" required
                                placeholder="Describe your listing in detail..."><?php echo htmlspecialchars($description); ?></textarea>
                            <div class="d-flex justify-content-between">
                                <div class="form-text">Minimum 10 characters.</div>
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
                            <label for="image" class="form-label fw-semibold">
                                Listing Image <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="image" name="image"
                                accept="image/jpeg,image/png,image/gif,image/webp" required>
                            <div class="form-text">JPG, PNG, GIF, or WEBP — max 5 MB.</div>
                            <div id="imagePreview" class="mt-2 d-none">
                                <img id="previewImg" src="#" alt="Preview"
                                    class="rounded" style="max-height:200px; max-width:100%; object-fit:cover;">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="media" class="form-label fw-semibold">
                                Media File <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <input type="file" class="form-control" id="media" name="media"
                                accept="video/mp4,audio/mpeg,audio/wav,audio/ogg,video/webm">
                            <div class="form-text">MP4, MP3, WAV, OGG, or WEBM — max 50 MB.</div>
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
                                        <i class="bi bi-pencil-square me-1"></i>Save as Draft
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                        name="status" id="statusPublished" value="published"
                                        <?php if ($status == 'published') {
                                            echo 'checked';
                                        } ?>>
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
    // Logic to update live textarea description length calculations
    const descBox = document.getElementById('description');
    const counterLabel = document.getElementById('descCount');

    function updateCounter() {
        counterLabel.textContent = descBox.value.length + ' / 2000';
    }
    descBox.addEventListener('input', updateCounter);
    updateCounter();

    // Handle preview window renders upon image submission loading
    document.getElementById('image').addEventListener('change', function() {
        const previewContainer = document.getElementById('imagePreview');
        const visualElement = document.getElementById('previewImg');

        if (this.files[0]) {
            const fileReader = new FileReader();
            fileReader.onload = function(e) {
                visualElement.src = e.target.result;
                previewContainer.classList.remove('d-none');
            };
            fileReader.readAsDataURL(this.files[0]);
        } else {
            previewContainer.classList.add('d-none');
        }
    });

    // Client side validation
    document.getElementById('createForm').addEventListener('submit', function(e) {
        const inputTitle = document.getElementById('title').value.trim();
        const inputDesc = document.getElementById('description').value.trim();
        const inputPrice = parseFloat(document.getElementById('price').value);
        const selectedCat = document.getElementById('category_id').value;
        const pickedImage = document.getElementById('image').files[0];
        const pickedMedia = document.getElementById('media').files[0];

        const clientErrors = [];

        if (inputTitle.length < 3) {
            clientErrors.push('Title must be at least 3 characters.');
        }
        if (inputDesc.length < 10) {
            clientErrors.push('Description must be at least 10 characters.');
        }
        if (isNaN(inputPrice) || inputPrice < 0) {
            clientErrors.push('Price must be a valid non-negative number.');
        }
        if (selectedCat === '') {
            clientErrors.push('Please select a category.');
        }
        if (!pickedImage) {
            clientErrors.push('Please upload a listing image.');
        }

        const validImgTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        const validMediaTypes = ['video/mp4', 'audio/mpeg', 'audio/wav', 'audio/ogg', 'video/webm'];

        if (pickedImage && !validImgTypes.includes(pickedImage.type)) {
            clientErrors.push('Image must be JPG, PNG, GIF, or WEBP.');
        }
        if (pickedImage && pickedImage.size > 5 * 1024 * 1024) {
            clientErrors.push('Image must be under 5 MB.');
        }
        if (pickedMedia && !validMediaTypes.includes(pickedMedia.type)) {
            clientErrors.push('Media must be MP4, MP3, WAV, OGG, or WEBM.');
        }
        if (pickedMedia && pickedMedia.size > 50 * 1024 * 1024) {
            clientErrors.push('Media file must be under 50 MB.');
        }

        // If validation errors detected, stop form submission and display errors in the alert box
        if (clientErrors.length > 0) {
            e.preventDefault();
            const displayBox = document.getElementById('errorBox');
            const listWrapper = document.getElementById('errorList');

            let outputHtml = '';
            for (let i = 0; i < clientErrors.length; i++) {
                outputHtml += '<li>' + clientErrors[i] + '</li>';
            }

            listWrapper.innerHTML = outputHtml;
            displayBox.style.display = 'block';
            window.scrollTo(0, 0);
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>