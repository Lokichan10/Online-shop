<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "product_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Функция для обрезки текста описания до указанной длины символов
function truncate_description($text, $length) {
    // Убираем возможные теги HTML
    $text = strip_tags($text);

    // Если длина текста меньше или равна заданной длине, возвращаем текст как есть
    if (mb_strlen($text) <= $length) {
        return $text;
    }

    // Обрезаем текст до указанной длины
    $text = mb_substr($text, 0, $length);

    // Удаляем последнее слово, чтобы текст не обрывался посередине слова
    $text = preg_replace('/\s+?(\S+)?$/', '', $text);

    // Добавляем многоточие в конец обрезанного текста
    $text .= '...';

    return $text;
}

// Обработка запроса на поиск продуктов
$sql = "SELECT * FROM products";
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $sql .= " WHERE name LIKE '%$search%'";
}
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Поиск товаров</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        .product-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }

        .product-card-content {
            padding: 1rem;
            flex-grow: 1;
        }

        .product-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .product-card p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .product-card-footer {
            padding: 1rem;
            background-color: #f0f0f0;
            border-top: 1px solid #ddd;
        }

        .product-card-footer p {
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        .product-card a {
            text-decoration: none;
            color: inherit;
        }
        a {
            color: black;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Магазин</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['username'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Зарегистрироваться</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Войти</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container mt-4">
    <h2 class="text-center mb-4">Поиск товаров</h2>
    <form class="mb-4" method="GET">
        <div class="input-group">
            <input type="text" class="form-control" name="search" placeholder="Введите название продукта">
            <button type="submit" class="btn btn-primary">Найти</button>
        </div>
    </form>

    <div class="product-container">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<a href="product.php?id=' . $row['id'] . '" class="product-card" style="text-decoration: none;">';
                echo '<img src="' . $row['image_url'] . '" alt="' . $row['name'] . '">';
                echo '<div class="product-card-content">';
                echo '<h3>' . $row['name'] . '</h3>';
                echo '<p>' . truncate_description($row['description'], 100) . '</p>';
                echo '</div>';
                echo '<div class="product-card-footer mt-auto">';
                echo '<p><strong>$' . $row['price'] . '</strong></p>';
                echo '</div>';
                echo '</a>';
            }
        } else {
            echo '<p class="text-center">Продукты не найдены.</p>';
        }
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></script>
</body>
</html>

<?php
$conn->close();
?>
