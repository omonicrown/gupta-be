<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
                <h3> Dear {{$details['custname']}} 🤗, </h3>
                <!-- <p> We hope this email finds you well.</p> -->
                <p> Let’s start the week strong and set those sales goals high. Remember, Gupta is here to help you reach more customers and make selling easier, so why not unlock the full power of our features?</p>

                <p> By subscribing to any of our plans, you’ll get access to everything you need to give your business that professional edge—from unlimited custom links to a fully branded mini store that stands out.</p>

                <p>Give it a try this week and see the difference for yourself! Let’s make this a winning week! 💼🚀</p>
                
                    <p>Visit www.mygupta.co to get the week started!</p>

                <p>If you need assistance or have any questions, feel free to contact our support team at
                    hello@mygupta.co. or via whatsapp on +2347025547335</p>
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