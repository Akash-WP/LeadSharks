<!DOCTYPE html>
<html>
<head>
    <title>Site Maintenance</title>
    <meta charset="utf-8" />
    <meta name="robots" content="noindex" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        body {
            text-align: center;
            padding: 20px;
            font: 20px Helvetica, sans-serif;
            color: #333;
            background-color: #ffffff;
        }
        @media (min-width: 768px) {
            body {
                padding-top: 100px;
            }
        }
        h1 {
            font-size: 50px;
            margin-bottom: 30px;
        }
        article {
            display: block;
            text-align: center;
            max-width: 750px;
            margin: 0 auto;
        }
        video {
            max-width: 80%;
            height: 40vh;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        a {
            color: #dc8100;
            text-decoration: none;
        }
        a:hover {
            color: #333;
            text-decoration: none;
        }
        @media (prefers-color-scheme: dark) {
            body {
                color: #efe8e8;
                background-color: #2e2929;
            }
            a {
                color: #dc8100;
            }
            a:hover {
                color: #efe8e8;
            }
        }
    </style>
</head>
<body>
    <article>
        <h1>We&rsquo;ll be back soon!</h1>

        <video autoplay muted loop playsinline>
            <source src="/lms/assets/images/Website Maintenance.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>

        <div>
            <p style="color: black;">
                Sorry for the inconvenience but we&rsquo;re performing some
                maintenance at the moment. If you need to you can always
                <a href="mailto:#">contact us</a>, otherwise we&rsquo;ll be
                back online shortly!
            </p>
            <p style="color: black;">&mdash; The Team</p>
        </div>
    </article>
</body>
</html>
