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

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);

    $sql = "SELECT * FROM products WHERE id = '$id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $productName = $row['name'];
        $productDescription = $row['description'];
        $productPrice = $row['price'];
        $productImageUrl = $row['image_url'];
    } else {
        die("Продукт не найден.");
    }
} else {
    die("Неверный запрос.");
}

// Обработка формы добавления комментария
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['username'])) {
    $comment = $conn->real_escape_string($_POST['comment']);
    $username = $_SESSION['username'];
    $product_id = $id;

    $stmt = $conn->prepare("INSERT INTO comments (product_id, username, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $product_id, $username, $comment);

    if ($stmt->execute()) {
        header("Location: product.php?id=$id");
        exit();
    } else {
        echo "Ошибка: " . $stmt->error;
    }

    $stmt->close();
}

// Получение комментариев для продукта
$comments_sql = "SELECT * FROM comments WHERE product_id = '$id' ORDER BY created_at DESC";
$comments_result = $conn->query($comments_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo $productName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .product-details {
            background-color: #fff;
            padding: 20px;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .product-details img {
            width: 100%;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .order-button {
            margin-top: 20px;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .banner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            z-index: 1001;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .banner-buttons {
            text-align: center;
            margin-top: 20px;
        }
        .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }
        .comment-section {
            margin-top: 20px;
        }
        .comment {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .comment h5 {
            margin-bottom: 5px;
        }
        .comment p {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="product-details">
                <h2 class="text-center mb-4"><?php echo $productName; ?></h2>
                <div class="row">
                    <div class="col-md-6">
                        <img src="<?php echo $productImageUrl; ?>" alt="<?php echo $productName; ?>" class="img-fluid">
                    </div>
                    <div class="col-md-6">
                        <p><strong>Описание:</strong></p>
                        <p><?php echo $productDescription; ?></p>
                        <p><strong>Цена:</strong> $<?php echo $productPrice; ?></p>
                        <button class="btn btn-primary order-button" onclick="toggleOverlay()">Заказать</button>
                    </div>
                </div>
            </div>

            <div class="overlay" id="overlay" onclick="toggleOverlay()"></div>

            <div class="banner" id="paymentBanner" style="width: 900px; height: 500px;">
                <span class="close-button" onclick="toggleOverlay()">&times;</span>
                <h3 class="text-center mb-3">Выберите способ оплаты</h3>
                <div class="banner-buttons">
                    <button class="btn btn-light" style="width: 400px; height: 400px; margin-left: 00px; color: black; border: 1px solid #000;">Оплата картой</button>
                    <button class="btn btn-light" style="width: 400px; height: 400px; margin-left: 30px; color: black; border: 1px solid #000;">Наличными при получении</button>
                </div>
            </div>

            <div class="comment-section">
                <h4>Комментарии</h4>
                <?php if ($comments_result->num_rows > 0): ?>
                    <?php while ($comment_row = $comments_result->fetch_assoc()): ?>
                        <div class="comment">
                            <h5><?php echo htmlspecialchars($comment_row['username']); ?></h5>
                            <p><?php echo htmlspecialchars($comment_row['comment']); ?></p>
                            <small><?php echo $comment_row['created_at']; ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Комментариев пока нет.</p>
                <?php endif; ?>

                <?php if (isset($_SESSION['username'])): ?>
                    <form action="product.php?id=<?php echo $id; ?>" method="post">
                        <div class="mb-3">
                            <label for="comment" class="form-label">Ваш комментарий</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Добавить комментарий</button>
                    </form>
                <?php else: ?>
                    <p>Пожалуйста, <a href="login.php">войдите</a> или <a href="register.php">зарегистрируйтесь</a>, чтобы оставить комментарий.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer class="bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <h5>О нас</h5>
                <p>Краткое описание вашей компании или проекта.</p>
            </div>
            <div class="col-md-3">
                <h5>Услуги</h5>
                <ul class="list-unstyled">
                    <li><a href="#">Веб-разработка</a></li>
                    <li><a href="#">Дизайн</a></li>
                    <li><a href="#">Консалтинг</a></li>
                    <li><a href="#">SEO</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Полезные ссылки</h5>
                <ul class="list-unstyled">
                    <li><a href="#">Блог</a></li>
                    <li><a href="#">Контакты</a></li>
                    <li><a href="#">Поддержка</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Социальные сети</h5>
                <ul class="list-unstyled">
                    <li><a href="#">Facebook</a></li>
                    <li><a href="#">Twitter</a></li>
                    <li><a href="#">Instagram</a></li>
                    <li><a href="#">LinkedIn</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <p>&copy; 2024 Ваша компания. Все права защищены.</p>
            </div>
            <div class="col-md-6 text-md-right">
                <ul class="list-inline">
                    <li class="list-inline-item"><a href="#">Политика конфиденциальности</a></li>
                    <li class="list-inline-item"><a href="#">Условия использования</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
    function toggleOverlay() {
        var overlay = document.getElementById('overlay');
        var banner = document.getElementById('paymentBanner');

        if (overlay.style.display === 'block') {
            overlay.style.opacity = 0;
            banner.style.opacity = 0;
            setTimeout(function() {
                overlay.style.display = 'none';
                banner.style.display = 'none';
            }, 300);
        } else {
            overlay.style.display = 'block';
            setTimeout(function() {
                overlay.style.opacity = 1;
                banner.style.display = 'block';
                banner.style.opacity = 1;
            }, 50);
        }
    }
</script>
</body>
</html>
