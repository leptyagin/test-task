<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{block name="title"}App{/block}</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            margin: 2rem auto;
            max-width: 720px;
            line-height: 1.5;
        }

        code {
            background: #f2f2f2;
            padding: .1rem .3rem;
            border-radius: .2rem;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: .4rem .6rem;
            text-align: left;
        }

        .error {
            color: #b00020;
        }
    </style>
</head>
<body>
    {block name="content"}{/block}
</body>
</html>
