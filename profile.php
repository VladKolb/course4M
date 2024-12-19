<?php
session_start();
include "db.php";

$uploadDir = 'resources/';
$default_image = 'resources/default.jpg';
$imagePath = $default_image; // По умолчанию используем дефолтное изображение
$error = true;

// Проверяем, есть ли запись с user_id в таблице
$stmt = $mysqli->prepare("SELECT picture_path, picture_data FROM picture_table WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();

// Проверяем, если запись существует
if ($stmt->num_rows > 0) {
    $stmt->bind_result($dbPicturePath, $dbPictureData);
    $stmt->fetch();

    if ($dbPicturePath) {
        $imagePath = $dbPicturePath;
    } else {
        // Если пути нет, используем дефолтное изображение
        $imagePath = $default_image;
    }
} else {
    // Если записи нет, используем дефолтное изображение
    $imagePath = $default_image;
}
$stmt->close();


function validateFile($filePath, $default_image) {
    global $error;
    $maxFileSize = 4 * 1024 * 1024; // 5 MB
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

    // Проверка на существование и доступность файла
    if (!file_exists($filePath)) {
        echo "<script>alert('Изображения не существует.');</script>";
        return '';
        //return $default_image;
    }

    if (!is_readable($filePath)) {
        echo "<script>alert('Нет прав на чтение файла.');</script>";
        return '';
        //return $default_image;
    }

    // Проверка прав доступа к директории
    if (!is_writable(dirname($filePath))) {
        echo "<script>alert('Нет прав на запись в директорию файла.');</script>";
        return '';
        //return $default_image;
    }

    // Проверка размера файла
    if (filesize($filePath) > $maxFileSize) {
        echo "<script>alert('Размер изображения превышает 4 МБ.');</script>";
        return '';
        //return $default_image;
    }

    // Проверка типа файла
    $fileType = mime_content_type($filePath);
    if (!in_array($fileType, $allowedTypes)) {
        echo "<script>alert('Недопустимый тип файла.');</script>";
        return '';
        //return $default_image;
    }

    if (!@getimagesize($filePath)) {
        echo "<script>alert('Файл поврежден или не является допустимым изображением.');</script>";
        return '';
        //return $default_image;
    }

    $error = false;
    return $filePath;
}

// Применяем проверку к изображению пользователя
$imagePath = validateFile($imagePath, $default_image);


if($error == false){
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        // Получаем путь к файлу и сохраняем его в папку resources
        $imageData = file_get_contents($_FILES['photo']['tmp_name']); // Чтение данных файла
        $fileName = basename($_FILES['photo']['name']);
        $uploadFile = $uploadDir . $fileName;
        $maxFileSize = 4 * 1024 * 1024; // 5 MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileType = mime_content_type($fileTmpPath);

        // Проверка на существование и доступность файла
        if (!file_exists($fileTmpPath) || !is_readable($fileTmpPath)) {
            echo "<script>alert('Изображение не существует или недоступно.');</script>";
            exit;
        }

        // Проверка прав доступа к директории
        if (!is_writable($uploadDir)) {
            echo "<script>alert('Нет прав на запись в директорию загрузки.');</script>";
            exit;
        }

        // Проверка размера файла
        if (filesize($fileTmpPath) > $maxFileSize) {
            echo "<script>alert('Размер изображения превышает 4 МБ.');</script>";
            exit;
        }

        // Проверка типа файла
        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>alert('Недопустимый тип файла. Пожалуйста, загрузите изображение в формате jpg, png или gif.');</script>";
            exit;
        }

        // Проверка на "битость" изображения
        if (!@getimagesize($fileTmpPath)) {
            echo "<script>alert('Файл поврежден или не является допустимым изображением.');</script>";
            exit;
        }
    
        // Перемещаем файл в директорию resources
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
            $imagePath = $uploadFile; // Устанавливаем путь к загруженному изображению
            $_SESSION["user_photo"] = $imagePath;
    
            // Проверяем, существует ли уже запись в базе данных
            $stmt = $mysqli->prepare("SELECT id FROM picture_table WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->store_result();
    
            if ($stmt->num_rows > 0) {
                // Если запись существует, обновляем picture_data и picture_path
                $updateStmt = $mysqli->prepare("UPDATE picture_table SET picture_data = ?, picture_path = ? WHERE user_id = ?");
                $updateStmt->bind_param("bsi", $imageData, $imagePath, $_SESSION['user_id']);
                $updateStmt->send_long_data(0, $imageData); // Передача BLOB данных
                $updateStmt->execute();
                $updateStmt->close();
                echo "Фотография успешно обновлена.";
            } else {
                // Если записи нет, добавляем новую запись
                $insertStmt = $mysqli->prepare("INSERT INTO picture_table (user_id, picture_data, picture_path) VALUES (?, ?, ?)");
                $insertStmt->bind_param("ibs", $_SESSION['user_id'], $imageData, $imagePath);
                $insertStmt->send_long_data(1, $imageData); // Передача BLOB данных
                $insertStmt->execute();
                $insertStmt->close();
                echo "Фотография успешно загружена.";
            }
    
            $stmt->close();
        } else {
            echo "Ошибка при загрузке файла.";
            exit;
        }
    }    
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-photo {
            width: 500px;
            height: 150px; 
            object-fit: cover;
            border-radius: 50%; 
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Профиль пользователя</h2>

   <form action="index.php" method="GET">
      <input type="submit" value="На главную">
   </form>
    
    <!-- Отображение фотографии -->
    <?php
    
    $photoPath = $imagePath;
    ?>
    <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Фотография профиля" class="profile-photo">
    <!-- Форма редактирования профиля -->
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="photo">Выберите фотографию:</label>
        <input type="file" name="photo" id="photo">

        <input type="text" name="first_name" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly placeholder="Имя">
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($_SESSION['user_surname']); ?>" readonly placeholder="Фамилия">
        <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" readonly placeholder="Email">
        
        <input type="submit" value="Сохранить">
    </form>
</div>

</body>
</html>
