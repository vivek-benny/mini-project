<?php
// manage_services.php
// Processes admin Manage Services submissions and redirects back to admin.php.

// 1) Database connection -------------------------------------------------------
/*
  If there is a shared db.php that initializes $pdo (PDO), include it here.
  Otherwise, the inline connection below will be used; adjust credentials as needed.
*/
// require_once __DIR__ . '/db.php';

if (!isset($pdo)) {
    $db_host = '127.0.0.1';
    $db_name = 'login'; // per provided SQL dump
    $db_user = 'root';
    $db_pass = '';
    $db_charset = 'utf8mb4';

    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    } catch (Throwable $e) {
        http_response_code(500);
        exit('Database connection failed.');
    }
}

// 2) Helpers (single definitions) ---------------------------------------------
if (!function_exists('redirect_with')) {
    function redirect_with($params = []) {
        $q = http_build_query($params);
        header("Location: admin.php" . ($q ? "?{$q}" : ""));
        exit;
    }
}

if (!function_exists('ipt')) {
    function ipt($key, $filter = FILTER_DEFAULT, $options = []) {
        return filter_input(INPUT_POST, $key, $filter, $options);
    }
}

if (!function_exists('clean_text')) {
    function clean_text($s) {
        if ($s === null) return null;
        return trim($s);
    }
}

if (!function_exists('minutes_to_estimated_time')) {
    function minutes_to_estimated_time(?int $mins): ?string {
        if ($mins === null || $mins <= 0) return null;
        return $mins . ' mins';
    }
}

// 3) Only accept POST ----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with(['error' => 1, 'why' => 'invalid_method']);
}

$action = ipt('action', FILTER_UNSAFE_RAW);
$action = is_string($action) ? trim($action) : '';

// 4) Routing -------------------------------------------------------------------
try {
    switch ($action) {
        // --------------------------- Services ---------------------------------
        case 'add': {
            $service_name = clean_text(ipt('service_name', FILTER_UNSAFE_RAW));
            $description  = clean_text(ipt('description', FILTER_UNSAFE_RAW));
            $price        = ipt('price', FILTER_VALIDATE_FLOAT);
            $duration     = ipt('duration', FILTER_VALIDATE_INT); // minutes
            $category     = clean_text(ipt('category', FILTER_UNSAFE_RAW));

            $duration_minutes = ($duration !== false && $duration !== null) ? $duration : null;
            $estimated_time   = minutes_to_estimated_time($duration_minutes);

            $stmt = $pdo->prepare("
                INSERT INTO services
                    (service_name, description, price, estimated_time, category, status, duration_minutes)
                VALUES
                    (?, ?, ?, ?, ?, 'active', ?)
            ");
            $stmt->execute([$service_name, $description, $price, $estimated_time, $category, $duration_minutes]);

            redirect_with(['msg' => 'service_added']);
        }

        case 'update': {
            $service_id   = ipt('service_id', FILTER_VALIDATE_INT);
            $service_name = clean_text(ipt('service_name', FILTER_UNSAFE_RAW));
            $description  = clean_text(ipt('description', FILTER_UNSAFE_RAW));
            $price        = ipt('price', FILTER_VALIDATE_FLOAT);
            $duration     = ipt('duration', FILTER_VALIDATE_INT);
            $category     = clean_text(ipt('category', FILTER_UNSAFE_RAW));

            if (!$service_id) {
                redirect_with(['error' => 1, 'why' => 'missing_service_id']);
            }

            $duration_minutes = ($duration !== false && $duration !== null) ? $duration : null;
            $estimated_time   = minutes_to_estimated_time($duration_minutes);

            $stmt = $pdo->prepare("
                UPDATE services
                   SET service_name = ?, description = ?, price = ?, estimated_time = ?, category = ?, duration_minutes = ?
                 WHERE service_id = ?
            ");
            $stmt->execute([$service_name, $description, $price, $estimated_time, $category, $duration_minutes, $service_id]);

            redirect_with(['msg' => 'service_updated']);
        }

        case 'delete': {
            $service_id = ipt('service_id', FILTER_VALIDATE_INT);
            if (!$service_id) {
                redirect_with(['error' => 1, 'why' => 'missing_service_id']);
            }

            $stmt = $pdo->prepare("DELETE FROM services WHERE service_id = ?");
            $stmt->execute([$service_id]);

            redirect_with(['msg' => 'service_deleted']);
        }

        case 'toggle_status': {
            $service_id = ipt('service_id', FILTER_VALIDATE_INT);
            $status     = ipt('status', FILTER_UNSAFE_RAW);
            if (!$service_id) {
                redirect_with(['error' => 1, 'why' => 'missing_service_id']);
            }
            $new_status = ($status === 'active') ? 'active' : 'inactive';
            $stmt = $pdo->prepare("UPDATE services SET status = ? WHERE service_id = ?");
            $stmt->execute([$new_status, $service_id]);
            redirect_with(['msg' => 'status_updated']);
        }

        // ------------------------- Service Views -------------------------------
        case 'add_serviceview': {
            $title       = clean_text(ipt('title', FILTER_UNSAFE_RAW));
            $description = clean_text(ipt('description', FILTER_UNSAFE_RAW));

            // Optional image upload (stores under ./images)
            $image_path = null;
            if (!empty($_FILES['image']['name'])) {
                $upload_dir = __DIR__ . '/images';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0775, true);
                }

                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $tmp = $_FILES['image']['tmp_name'] ?? null;
                $mime = ($tmp && file_exists($tmp)) ? mime_content_type($tmp) : null;
                if (!$mime || !isset($allowed[$mime])) {
                    redirect_with(['error' => 1, 'why' => 'invalid_image']);
                }
                $ext = $allowed[$mime];
                $basename = 'sv_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest_fs = $upload_dir . '/' . $basename;
                if (!move_uploaded_file($tmp, $dest_fs)) {
                    redirect_with(['error' => 1, 'why' => 'upload_failed']);
                }
                $image_path = 'images/' . $basename; // web path
            } else {
                // Placeholder; ensure file exists or adjust to your preferred default
                $image_path = 'images/placeholder.jpg';
            }

            $stmt = $pdo->prepare("INSERT INTO serviceview (title, image, description) VALUES (?, ?, ?)");
            $stmt->execute([$title, $image_path, $description]);

            redirect_with(['msg' => 'serviceview_added']);
        }

        case 'update_serviceview': {
            $serviceview_id = ipt('serviceview_id', FILTER_VALIDATE_INT);
            $title          = clean_text(ipt('title', FILTER_UNSAFE_RAW));
            $description    = clean_text(ipt('description', FILTER_UNSAFE_RAW));

            if (!$serviceview_id) {
                redirect_with(['error' => 1, 'why' => 'missing_serviceview_id']);
            }

            $new_image_path = null;
            if (!empty($_FILES['image']['name'])) {
                $upload_dir = __DIR__ . '/images';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0775, true);
                }

                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $tmp = $_FILES['image']['tmp_name'] ?? null;
                $mime = ($tmp && file_exists($tmp)) ? mime_content_type($tmp) : null;
                if (!$mime || !isset($allowed[$mime])) {
                    redirect_with(['error' => 1, 'why' => 'invalid_image']);
                }
                $ext = $allowed[$mime];
                $basename = 'sv_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest_fs = $upload_dir . '/' . $basename;
                if (!move_uploaded_file($tmp, $dest_fs)) {
                    redirect_with(['error' => 1, 'why' => 'upload_failed']);
                }
                $new_image_path = 'images/' . $basename;
            }

            if ($new_image_path) {
                $stmt = $pdo->prepare("UPDATE serviceview SET title = ?, image = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $new_image_path, $description, $serviceview_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE serviceview SET title = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $description, $serviceview_id]);
            }

            redirect_with(['msg' => 'serviceview_updated']);
        }

        case 'delete_serviceview': {
            $serviceview_id = ipt('serviceview_id', FILTER_VALIDATE_INT);
            if (!$serviceview_id) {
                redirect_with(['error' => 1, 'why' => 'missing_serviceview_id']);
            }

            $stmt = $pdo->prepare("DELETE FROM serviceview WHERE id = ?");
            $stmt->execute([$serviceview_id]);

            redirect_with(['msg' => 'serviceview_deleted']);
        }

        // ------------------------ Service Details ------------------------------
        case 'add_service_details': {
            // Per SQL dump, service_details.service_id references serviceview.id (FK)
            $serviceview_id = ipt('detail_service_id', FILTER_VALIDATE_INT);
            $whats_included = clean_text(ipt('whats_included', FILTER_UNSAFE_RAW));
            $why_choose     = clean_text(ipt('why_choose', FILTER_UNSAFE_RAW));

            if (!$serviceview_id) {
                redirect_with(['error' => 1, 'why' => 'missing_serviceview_id_for_details']);
            }

            $stmt = $pdo->prepare("
                INSERT INTO service_details (service_id, whats_included, why_choose)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$serviceview_id, $whats_included, $why_choose]);

            redirect_with(['msg' => 'service_details_added']);
        }

        case 'delete_service_details': {
            $detail_id = ipt('detail_id', FILTER_VALIDATE_INT);
            if (!$detail_id) {
                redirect_with(['error' => 1, 'why' => 'missing_detail_id']);
            }

            $stmt = $pdo->prepare("DELETE FROM service_details WHERE id = ?");
            $stmt->execute([$detail_id]);

            redirect_with(['msg' => 'service_details_deleted']);
        }

        // --------------------------- Unknown -----------------------------------
        default: {
            // Fallback: if a toggle was sent without action param
            $service_id = ipt('service_id', FILTER_VALIDATE_INT);
            $status     = ipt('status', FILTER_UNSAFE_RAW);
            if ($service_id && is_string($status)) {
                $new_status = ($status === 'active') ? 'active' : 'inactive';
                $stmt = $pdo->prepare("UPDATE services SET status = ? WHERE service_id = ?");
                $stmt->execute([$new_status, $service_id]);
                redirect_with(['msg' => 'status_updated']);
            }
            redirect_with(['error' => 1, 'why' => 'unknown_action']);
        }
    }
} catch (Throwable $e) {
    // In production: error_log($e->getMessage());
    redirect_with(['error' => 1]);
}
