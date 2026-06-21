<?php
/**
 * API Endpoint: User Notifications
 * GET /api/notifications.php
 * POST /api/notifications.php?action=mark-read&id=123
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/NotificationService.php';

require_login();

$notificationService = new NotificationService($pdo);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $limit = (int) ($_GET['limit'] ?? 50);
        $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';

        $notifications = $notificationService->getUserNotifications(
            $_SESSION['user_id'],
            $unreadOnly,
            $limit
        );

        $unreadCount = $notificationService->getUnreadCount($_SESSION['user_id']);

        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $notificationId = (int) ($_POST['id'] ?? 0);

        if ($action === 'mark-read' && $notificationId > 0) {
            $result = $notificationService->markAsRead($notificationId, $_SESSION['user_id']);
            echo json_encode(['success' => $result]);
        } elseif ($action === 'mark-all-read') {
            $result = $notificationService->markAllAsRead($_SESSION['user_id']);
            echo json_encode(['success' => $result]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
