<?php
require_once '../includes/db.php';
header('Content-Type: application/json; charset=utf-8');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user   = current_user();
if (!$user) json_response(['error'=>'Unauthorized'], 401);

// ── CREATE ASSIGNMENT (Admin/Teacher) ──
if ($action === 'create' && in_array($user['role'],['admin','teacher'])) {
    $course_id = intval($_POST['course_id']  ?? 0);
    $title     = trim($_POST['title']        ?? '');
    $desc      = trim($_POST['description']  ?? '');
    $due       = !empty($_POST['due_date'])  ? $_POST['due_date']  : null;
    $marks     = intval($_POST['max_marks']  ?? 100);
    if (!$course_id || !$title) json_response(['ok'=>false,'msg'=>'Title ও course আবশ্যক।']);
    $pdo->prepare("INSERT INTO assignments (course_id,teacher_id,title,description,due_date,max_marks) VALUES (?,?,?,?,?,?)")
        ->execute([$course_id,$user['id'],$title,$desc,$due,$marks]);
    json_response(['ok'=>true,'assignment_id'=>$pdo->lastInsertId()]);
}

// ── GET ASSIGNMENTS FOR COURSE (Student/Teacher) ──
if ($action === 'list') {
    $course_id = intval($_GET['course_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT a.*,u.name AS teacher_name,
        (SELECT status FROM assignment_submissions WHERE assignment_id=a.id AND student_id=?) AS my_status
        FROM assignments a JOIN users u ON u.id=a.teacher_id
        WHERE a.course_id=? AND a.is_active=1 ORDER BY a.due_date ASC
    ");
    $stmt->execute([$user['id'],$course_id]);
    json_response(['ok'=>true,'assignments'=>$stmt->fetchAll()]);
}

// ── SUBMIT ASSIGNMENT (Student) ──
if ($action === 'submit' && $user['role']==='student') {
    $ass_id     = intval($_POST['assignment_id'] ?? 0);
    $answer     = trim($_POST['answer_text']     ?? '');
    $file_url   = trim($_POST['file_url']        ?? '');
    if (!$ass_id || (!$answer && !$file_url)) json_response(['ok'=>false,'msg'=>'Answer আবশ্যক।']);

    try {
        $pdo->prepare("INSERT INTO assignment_submissions (assignment_id,student_id,answer_text,file_url) VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE answer_text=VALUES(answer_text),file_url=VALUES(file_url),submitted_at=NOW(),status='submitted'")
            ->execute([$ass_id,$user['id'],$answer,$file_url]);
        json_response(['ok'=>true,'msg'=>'Assignment submit হয়েছে ✅']);
    } catch(\PDOException $e) {
        json_response(['ok'=>false,'msg'=>'সমস্যা হয়েছে।']);
    }
}

// ── GRADE SUBMISSION (Admin/Teacher) ──
if ($action === 'grade' && in_array($user['role'],['admin','teacher'])) {
    $sub_id   = intval($_POST['submission_id'] ?? 0);
    $marks    = intval($_POST['marks']          ?? 0);
    $feedback = trim($_POST['feedback']         ?? '');
    $pdo->prepare("UPDATE assignment_submissions SET marks=?,feedback=?,status='graded' WHERE id=?")
        ->execute([$marks,$feedback,$sub_id]);
    json_response(['ok'=>true,'msg'=>'Grade সেভ হয়েছে ✅']);
}

// ── GET SUBMISSIONS (Admin/Teacher) ──
if ($action === 'submissions' && in_array($user['role'],['admin','teacher'])) {
    $ass_id = intval($_GET['assignment_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT s.*,u.name AS student_name,u.phone
        FROM assignment_submissions s JOIN users u ON u.id=s.student_id
        WHERE s.assignment_id=? ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$ass_id]);
    json_response(['ok'=>true,'submissions'=>$stmt->fetchAll()]);
}

// ── MY SUBMISSIONS (Student) ──
if ($action === 'my_submissions' && $user['role']==='student') {
    $course_id = intval($_GET['course_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT s.*,a.title,a.max_marks,a.due_date
        FROM assignment_submissions s JOIN assignments a ON a.id=s.assignment_id
        WHERE s.student_id=? AND a.course_id=? ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$user['id'],$course_id]);
    json_response(['ok'=>true,'submissions'=>$stmt->fetchAll()]);
}

json_response(['error'=>'Invalid action'], 400);
