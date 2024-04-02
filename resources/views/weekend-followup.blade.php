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
                <p> We hope this email finds you well.</p>
                <p> Thank you for being a valued user of our platform! Your support and trust in our services have enabled us to continually improve and serve your needs effectively.</p>
              
              <p></p>
              

              <p>
                <span style="font-weight: 500;">To ensure that we are meeting your expectations and enhancing your experience, we would greatly appreciate your feedback. We kindly ask if you could spare a moment to answer a few questions:</span><br /><br />
                <span>-1. Have you encountered any challenges while using our platform? If so, could you please provide details?</span><br /><br />
                <span>-2. Are there any specific features or changes you would like to see implemented to improve your experience?</span><br /><br />
                <span>-3. If you have not subscribed to our paid services, could you please share the main reasons for this decision?</span><br /><br />
                
                <!-- <span>- You get guaranteed job placement for work practise, then possible permanent contract</span><br /><br />
                <span><a href="https://afriproedu.com/course-details/2"> Click here for more information </a></span><br /><br /> -->

            </p>

            <p>Please rest assured that your feedback is anonymous and will be used solely to enhance our platform and better serve your needs.</p>

            <p>Thank you once again for your continued support and for helping us improve. We look forward to hearing from you soon!</p>



                <!-- <p> Additionally, we've created a vibrant community on our Telegram channel where you can connect with
                    us and fellow users. Feel free to join the conversation, ask questions, and share your thoughts. You
                    can find us on Telegram at <a href="https://t.me/gupta_community">https://t.me/gupta_community</a>.
                </p>
                <p> Thank you for being a part of our journey. We look forward to serving you and making your experience
                    with us exceptional.</p> -->

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