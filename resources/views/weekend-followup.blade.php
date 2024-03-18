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
    <div>
        <span style="display:flex;justify:center;text-align:center;">
            <img src="https://www.mygupta.co/gt3.png" />
        </span>

    </div>
    <div class="wrapper">

        <div class="content">

            <div class="header">
                <h3> Hello {{$details['custname']}}, </h3>
                <p>Hope you're doing great! We've got some thrilling updates to kickstart your week.</p>
                <p>Firstly, we're excited to announce that the Gupta Marketplace is launching in the first week of next month! This means that your products will be showcased on our platform, reaching a wider audience of potential customers. Get ready to showcase your offerings to the world!</p>
                
                <p>In addition, we wanted to express our gratitude for your continued support. As a token of appreciation, we're extending your subscription by an additional 2 months, giving you more time to explore all the features Gupta has to offer, including the upcoming Marketplace.</p>

                <p>This is an incredible opportunity to expand your business and reach new heights. Let's make this journey together and drive success together!</p>
                
                <p> Have questions or need assistance? Reach out to us on WhatsApp at +234 913 729 4656 or drop us an email at hello@mygupta.co. Join our vibrant community on Telegram for real-time updates, discussions, and more:  <a href="https://t.me/+l40Q-6IHxA1mMzJk">https://t.me/+l40Q-6IHxA1mMzJk</a>.
                </p>
              
                
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