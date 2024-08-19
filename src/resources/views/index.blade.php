<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <title>AmoCRM Api</title>
</head>
<body>
    <div class="main-center">
        <div class="row">
            <div class="d-flex align-items-center flex-column">
                <form action="{{ route('api') }}" method="POST">
                    @csrf
                    @method('POST')
                    
                    <div class="form-group p-2">
                        <label for="name">Имя</label>
                        <input class="form-control" name="name" id="name" type="text" placeholder="Ваше имя...">
                    </div>

                    <div class="form-group p-2">
                        <label for="email">Почта</label>
                        <input class="form-control" name="email" id="email" type="text" placeholder="Ваша почта...">
                    </div>

                    <div class="form-group p-2">    
                        <label for="phone">Телефон</label>
                        <input class="form-control" name="phone" id="phone" type="phone" placeholder="Ваш номер телефона...">
                    </div>
                    
                    <div class="form-group p-2">
                        <label for="price">Цена</label>
                        <input class="form-control" name="price" id="price" type="text" placeholder="Цена...">
                    </div>
                    
                    <div class="form-group p-2 text-center">
                        <button type="submit" class="btn btn-primary">Отправить</button>
                    </div>
                
                </form>
                
            </div>
        </div>
    </div>
    
   
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>