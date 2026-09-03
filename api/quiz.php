<?php
require_once '../includes/db.php';
header('Content-Type: application/json; charset=utf-8');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user   = current_user();
if (!$user) json_response(['error'=>'Unauthorized'], 401);

// ── GET QUIZ (Student) ──
if ($action === 'get' && $user['role']==='student') {
    $quiz_id = intval($_GET['quiz_id'] ?? 0);
    $q = $pdo->prepare("SELECT * FROM quizzes WHERE id=? AND is_active=1");
    $q->execute([$quiz_id]); $quiz = $q->fetch();
    if (!$quiz) json_response(['error'=>'Quiz not found'], 404);

    // Check attempts
    $att = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND student_id=?");
    $att->execute([$quiz_id, $user['id']]);
    $attempt_count = $att->fetchColumn();
    if ($quiz['max_attempts'] && $attempt_count >= $quiz['max_attempts']) {
        json_response(['error'=>'সর্বোচ্চ attempt শেষ।', 'attempts_used'=>$attempt_count]);
    }

    // Get questions (shuffle, hide correct answer)
    $qs = $pdo->prepare("SELECT id,question,type,option_a,option_b,option_c,option_d,marks FROM quiz_questions WHERE quiz_id=? ORDER BY sort_order");
    $qs->execute([$quiz_id]);
    $questions = $qs->fetchAll();

    json_response([
        'quiz'      => $quiz,
        'questions' => $questions,
        'attempts_used' => $attempt_count,
    ]);
}

// ── SUBMIT QUIZ (Student) ──
if ($action === 'submit' && $user['role']==='student') {
    $quiz_id = intval($_POST['quiz_id'] ?? 0);
    $answers = json_decode($_POST['answers'] ?? '{}', true);
    $time_taken = intval($_POST['time_taken'] ?? 0);

    $q = $pdo->prepare("SELECT * FROM quizzes WHERE id=? AND is_active=1");
    $q->execute([$quiz_id]); $quiz=$q->fetch();
    if (!$quiz) json_response(['error'=>'Quiz not found'], 404);

    // Check attempts
    $att = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id=? AND student_id=?");
    $att->execute([$quiz_id, $user['id']]);
    if ($quiz['max_attempts'] && $att->fetchColumn() >= $quiz['max_attempts']) {
        json_response(['error'=>'সর্বোচ্চ attempt শেষ।']);
    }

    // Get questions with correct answers
    $qs = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY sort_order");
    $qs->execute([$quiz_id]); $questions=$qs->fetchAll();

    // Grade
    $score=0; $total=0; $results=[];
    foreach ($questions as $q) {
        $total += $q['marks'];
        $given  = strtolower(trim($answers[$q['id']] ?? ''));
        $correct= strtolower(trim($q['correct']));
        $is_correct = ($given === $correct);
        if ($is_correct) $score += $q['marks'];
        $results[$q['id']] = [
            'given'      => $given,
            'correct'    => $correct,
            'is_correct' => $is_correct,
            'explanation'=> $q['explanation'],
            'marks'      => $q['marks'],
        ];
    }
    $pct    = $total > 0 ? round($score/$total*100) : 0;
    $passed = $pct >= $quiz['pass_mark'];

    // Save attempt
    $pdo->prepare("INSERT INTO quiz_attempts (quiz_id,student_id,answers,score,total_marks,percentage,passed,time_taken) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$quiz_id,$user['id'],json_encode($answers),$score,$total,$pct,$passed?1:0,$time_taken]);
    $attempt_id = $pdo->lastInsertId();

    // Auto-issue certificate if course completed + passed
    if ($passed && isset($quiz['course_id'])) {
        issue_certificate_if_eligible($pdo, $user['id'], $quiz['course_id']);
    }

    json_response([
        'ok'          => true,
        'score'       => $score,
        'total_marks' => $total,
        'percentage'  => $pct,
        'passed'      => $passed,
        'pass_mark'   => $quiz['pass_mark'],
        'results'     => $results,
        'attempt_id'  => $attempt_id,
    ]);
}

// ── CREATE QUIZ (Admin/Teacher) ──
if ($action === 'create_quiz' && in_array($user['role'],['admin','teacher'])) {
    $course_id  = intval($_POST['course_id']  ?? 0);
    $title      = trim($_POST['title']        ?? '');
    $desc       = trim($_POST['description']  ?? '');
    $time_limit = !empty($_POST['time_limit']) ? intval($_POST['time_limit']) : null;
    $pass_mark  = intval($_POST['pass_mark']  ?? 60);
    $max_att    = intval($_POST['max_attempts']?? 3);

    if (!$course_id || !$title) json_response(['ok'=>false,'msg'=>'Title ও course আবশ্যক।']);

    $pdo->prepare("INSERT INTO quizzes (course_id,title,description,time_limit,pass_mark,max_attempts) VALUES (?,?,?,?,?,?)")
        ->execute([$course_id,$title,$desc,$time_limit,$pass_mark,$max_att]);
    json_response(['ok'=>true,'quiz_id'=>$pdo->lastInsertId()]);
}

// ── ADD QUESTION ──
if ($action === 'add_question' && in_array($user['role'],['admin','teacher'])) {
    $quiz_id = intval($_POST['quiz_id'] ?? 0);
    $pdo->prepare("INSERT INTO quiz_questions (quiz_id,question,type,option_a,option_b,option_c,option_d,correct,explanation,marks) VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $quiz_id,
            trim($_POST['question'] ?? ''),
            $_POST['type'] ?? 'mcq',
            trim($_POST['option_a'] ?? ''),
            trim($_POST['option_b'] ?? ''),
            trim($_POST['option_c'] ?? ''),
            trim($_POST['option_d'] ?? ''),
            strtolower(trim($_POST['correct'] ?? 'a')),
            trim($_POST['explanation'] ?? ''),
            intval($_POST['marks'] ?? 1),
        ]);
    json_response(['ok'=>true,'question_id'=>$pdo->lastInsertId()]);
}

// ── GET QUIZ RESULTS (Admin) ──
if ($action === 'results' && $user['role']==='admin') {
    $quiz_id = intval($_GET['quiz_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT qa.*,u.name AS student_name
        FROM quiz_attempts qa JOIN users u ON u.id=qa.student_id
        WHERE qa.quiz_id=? ORDER BY qa.attempted_at DESC
    ");
    $stmt->execute([$quiz_id]);
    json_response(['ok'=>true,'attempts'=>$stmt->fetchAll()]);
}

// ── GET MY ATTEMPTS (Student) ──
if ($action === 'my_attempts' && $user['role']==='student') {
    $quiz_id = intval($_GET['quiz_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM quiz_attempts WHERE quiz_id=? AND student_id=? ORDER BY attempted_at DESC");
    $stmt->execute([$quiz_id,$user['id']]);
    json_response(['ok'=>true,'attempts'=>$stmt->fetchAll()]);
}

// ── Helper: Issue certificate ──
function issue_certificate_if_eligible($pdo, $student_id, $course_id) {
    // Check if all lessons completed
    $total_q = $pdo->prepare("SELECT COUNT(*) FROM course_lessons WHERE course_id=? AND is_active=1");
    $total_q->execute([$course_id]); $total=$total_q->fetchColumn();

    $done_q = $pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE course_id=? AND student_id=? AND is_completed=1");
    $done_q->execute([$course_id,$student_id]); $done=$done_q->fetchColumn();

    if ($total > 0 && $done >= $total) {
        // Already issued?
        $ex = $pdo->prepare("SELECT id FROM certificates WHERE course_id=? AND student_id=?");
        $ex->execute([$course_id,$student_id]);
        if (!$ex->fetch()) {
            $cert_id = 'TAQWIM-'.strtoupper(substr(md5($student_id.$course_id.time()),0,4)).'-'.strtoupper(substr(md5(time().$student_id),0,4));
            $pdo->prepare("INSERT INTO certificates (course_id,student_id,cert_id) VALUES (?,?,?)")
                ->execute([$course_id,$student_id,$cert_id]);
        }
    }
}

json_response(['error'=>'Invalid action'], 400);
