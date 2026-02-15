<?php
//require_once __DIR__.'/admin-auth.php';
require_once __DIR__.'/../Config/database.php';

if(!isset($_SESSION['admin_id'])){
    http_response_code(403);
    echo json_encode(['success'=>false,'msg'=>'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$ids = $data['ids'] ?? [];

if(!$ids || !in_array($action, ['restore','toggle'])){
    echo json_encode(['success'=>false,'msg'=>'Invalid request']);
    exit;
}

$ids = array_map('intval', $ids);

try {
    if($action==='restore'){
        foreach($ids as $id){
            $stmt = $pdo->prepare("SELECT * FROM archive WHERE id = :id");
            $stmt->execute(['id'=>$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$row) continue;

            // Insert into register
            $stmtInsert = $pdo->prepare("INSERT INTO register 
                (lrn, full_name, id_number, grade, strand, course, home_address, guardian_name, guardian_contact, photo, created_at, restored_at)
                VALUES (:lrn, :full_name, :id_number, :grade, :strand, :course, :home_address, :guardian_name, :guardian_contact, :photo, :created_at, NOW())");
            $stmtInsert->execute([
                'lrn'=>$row['lrn'],
                'full_name'=>$row['full_name'],
                'id_number'=>$row['id_number'],
                'grade'=>$row['grade'],
                'strand'=>$row['strand'],
                'course'=>$row['course'],
                'home_address'=>$row['home_address'],
                'guardian_name'=>$row['guardian_name'],
                'guardian_contact'=>$row['guardian_contact'],
                'photo'=>$row['photo'],
                'created_at'=>$row['created_at']
            ]);

            // Delete from archive
            $stmtDel = $pdo->prepare("DELETE FROM archive WHERE id=:id");
            $stmtDel->execute(['id'=>$id]);
        }
        echo json_encode(['success'=>true,'msg'=>'Selected records restored.']);
    }

    if($action==='toggle'){
        foreach($ids as $id){
            $stmt = $pdo->prepare("SELECT status FROM archive WHERE id = :id");
            $stmt->execute(['id'=>$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$row) continue;
            $newStatus = ($row['status']==='Released') ? 'Not Released' : 'Released';
            $releasedAt = ($newStatus==='Released') ? date('Y-m-d H:i:s') : null;
            $stmtUpd = $pdo->prepare("UPDATE archive SET status=:status, released_at=:released_at WHERE id=:id");
            $stmtUpd->execute(['status'=>$newStatus,'released_at'=>$releasedAt,'id'=>$id]);
        }
        echo json_encode(['success'=>true,'msg'=>'Status toggled for selected records.']);
    }

} catch(PDOException $e){
    echo json_encode(['success'=>false,'msg'=>'Database error: '.$e->getMessage()]);
}
