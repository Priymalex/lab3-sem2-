<?php
$config = include('db_config.php');

$name = $_POST['FIO'] ?? '';        
$tel = $_POST['telep'] ?? '';      
$email = $_POST['mail'] ?? '';
$dateborn = $_POST['date'] ?? '';
$sex = $_POST['sex'] ?? '';
$bio = $_POST['bio'] ?? '';
$languages = $_POST['language'] ?? [];
$agreement = isset($_POST['Agreement']);

$errors = [];

if (empty($name)) { 
    $errors[] = "Поле ФИО пустое";
} elseif (strlen($name) > 150) {
    $errors[] = "ФИО слишком длинное";
} elseif (!preg_match('/^[a-zA-Zа-яёА-ЯЁ ]+$/u', $name)) {
    $errors[] = "В ФИО можно только буквы и пробелы";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Почта введена неправильно";
}

if (empty($sex)) {
    $errors[] = "Поле 'Пол' не может быть пустым.";
} elseif (!in_array($sex, ['Male', 'Female'])) { 
    $errors[] = "Выбран недопустимый пол.";
}

if (empty($languages)) {
    $errors[] = "Необходимо выбрать хотя бы один язык программирования.";
}

if (empty($tel)) { 
    $errors[] = "Поле 'Телефон' не может быть пустым.";
} elseif (!preg_match('/^[\+\d\s\-\(\)]+$/', $tel)) {
    $errors[] = "Телефон введен некорректно.";
} elseif (strlen($tel) < 6 || strlen($tel) > 20) {
    $errors[] = "Телефон должен содержать от 6 до 20 символов.";
}

if (!empty($dateborn) && !strtotime($dateborn)) {
    $errors[] = "Дата рождения указана некорректно.";
}

if (!$agreement) {
    $errors[] = "Необходимо согласиться с правилами.";
}

if (!empty($errors)) {
    echo "<h2>Ошибки:</h2>";
    foreach ($errors as $error) { 
        echo "- $error<br>"; 
    }
    exit;
}


try {
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8", 
        $config['user'], 
        $config['pass']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->beginTransaction();


    $stmt = $db->prepare("INSERT INTO Frequest (name, tel, email, dateborn, sex, bio, agree) 
                          VALUES (:name, :tel, :email, :dateborn, :sex, :bio, :agree)");
    $stmt->execute([
        ':name' => $name,
        ':tel' => $tel,
        ':email' => $email,
        ':dateborn' => $dateborn,
        ':sex' => $sex,
        ':bio' => $bio,
        ':agree' => $agreement ? 1 : 0
    ]);

    $requestId = $db->lastInsertId();

    $getLangId = $db->prepare("SELECT language_id FROM LANGUAGES WHERE language_name = ?");
    $insertConn = $db->prepare("INSERT INTO Connect (request_id, language_id) VALUES (?, ?)");

    foreach ($languages as $langName) {
        $getLangId->execute([$langName]);
        $row = $getLangId->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $insertConn->execute([$requestId, $row['language_id']]);
        }
    }

    $db->commit();
    echo "<h2>Данные успешно сохранены!</h2>";

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Ошибка базы данных: " . $e->getMessage();
}
