<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Links Information</title>
</head>
<style>
    body {
        background-color: #ffffff;
        font-family: sans-serif;
        -webkit-font-smoothing: antialiased;
        font-size: 14px;
        /* line-height: 1.4; */
        margin: 0;
        padding: 0;
        -ms-text-size-adjust: 100%;
        -webkit-text-size-adjust: 100%;
    }

    .wrapper {
        background-color: #F2F5F8;
        margin: auto;
        margin-top: 50px;
        margin-bottom: 40px;
        width: 640px;
        padding-top: 15px;
        padding-bottom: 15px;
        padding-left: 32px;
        padding-right: 32px;

    }

    .content {
        background-color: white;
        width: 576px;
        margin: auto;
        margin-top: 20px;
        padding: 32px;
    }

    .header h3 {
        font-size: 16px;
        color: #333333;
    }

    .text-content {
        margin-top: 20px;
    }

    .text-content p {
        font-size: 14px;
        color: #333333;
    }

    .button {
        border: none;
        background-color: #0071BC;
        color: #ffffff;
        padding: 12px 22px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        border-radius: 20px;
    }

    .footer {
        padding: 32px;
    }

    .footer p {
        font-size: 14px;
        color: #333333;
    }

    .footer-end {
        display: flex;
        justify-content: space-between;

    }

    .footer-end h3 {
        color: #6A7C94;
        font-style: italic;

    }

    .footer-social img {
        padding-left: 10px;
        margin-top: 8px;
    }

    .logo img {
        text-align: center;
        position: relative;
        display: flex;
        justify: center;
        background-color: #3F83F8;
        padding-top: 10px;
        padding-bottom: 10px;
        padding-left: 10px;
        padding-right: 10px;
    }
</style>

<body>
    <div class="logo">
        <span style="display:flex;justify:center;text-align:center;">
            <img src="https://www.mygupta.co/gt3.png" />
        </span>

    </div>
    <div class="wrapper">

        <div class="content">

            <div class="header">
                <h3> Dear Vendor, </h3>
                <p> We’ve gathered all your created links for easy access. Below, you’ll find the details of your links,
                    including direct access to each one. Feel free to review them at your convenience.</p>

                <p></p>

                <h2>Your Links Information</h2>

                <b>Whatsapp Links</b>
                <ul>
                    @foreach($data['session_links']->link as $link)
                        <li><a href="https://link.mygupta.co/{{ $link->name }}">link.mygupta.co/{{ $link->name }}</a> -
                            {{ $link->type }}</li>
                    @endforeach
                </ul>

                <b>Redirect Links</b>
                <ul>
                    @foreach($data['redirect_links']->redirectLinks as $link)
                        <li><a href="https://link.mygupta.co/{{ $link->name }}">link.mygupta.co/{{ $link->name }}</a> </li>
                    @endforeach
                </ul>

                <b>Multi Links</b>
                <ul>
                    @foreach($data['multi_links']->multiLink as $link)
                        <li><a href="https://mygupta.co/{{ $link->name }}">mygupta.co/{{ $link->name }}</a> </li>
                    @endforeach
                </ul>

                <b>Mini Store URLs</b>
                <ul>
                    @foreach($data['mini_store'] as $link)
                        <li><a href="https://mygupta.co/{{ $link->name }}">mygupta.co/store/{{ $link->link_name }}</a> </li>
                    @endforeach
                </ul>

                <p>If you need assistance or have any questions, feel free to contact our support team at hello@mygupta.co .</p>

            </div>
            <div class="text-content">


                <p>Best regards,</p>
            </div>
        </div>



        <div class="footer">
            <p>Sent by Gupta © 2024. All Rights Reserved.</p>
        </div>
        <div class="footer-end">
            <div>
                <h3>Gupta</h3>
            </div>
            <div class="footer-social">
                <img src="https://www.mygupta.co/twitter.jpeg" />
                <img src="https://www.mygupta.co/facebook.jpeg" />
                <img src="https://www.mygupta.co/linkedin.jpeg" />
            </div>
        </div>
    </div>
</body>

</html>








