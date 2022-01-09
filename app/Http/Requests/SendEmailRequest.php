<?php

namespace App\Http\Requests;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/**
 * @since 2-jan-2022
 *
 * This class is respnsible for sending the message to the given email id and token.
 */
class SendEmailRequest
{

     /**
     * @param $email,$token
     *
     * This function takes two args from the function in UserContoller and successfully
     * sends the token as a reset link to the user email id.
     */
    public function sendEmail($user,$token)
    {
        $data ="Hi,".$user->firstname."<br>Your password Reset Link <br>".$token;

        $mail = new PHPMailer(true);

        try
        {
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_USERNAME');
            $mail->Password   = env('MAIL_PASSWORD');
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->setFrom(env('MAIL_USERNAME'),env('MAIL_FROM_NAME'));
            $mail->addAddress($user->email);
            $mail->isHTML(true);
            $mail->Subject = env('MAIL_SUBJECT');
            $mail->Body = $data;
            $dt = $mail->send();
            sleep(3);

           if($dt)
                return true;
            else
                return false;

        }
        catch (Exception $e)
        {
            return back()->with('error','Message could not be sent.');
        }
    }
}
