<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mail\SendMailable;
use Mail;
use DB;
use Session;
use Redirect;
use JWTFactory;
use JWTAuth;
use App\Models\Contact;

class PagesController extends Controller {

    public function index(Request $request) {
        $this->getSession();
        $slug = $request->segment(1);
        $pageInfo = DB::table('pages')->where('slug', $slug)->first();
        
        $pageTitle = $pageInfo->title;
        return view('pages.index', ['title' => $pageTitle, 'pageInfo' => $pageInfo]);
    }

    public function pageIndex(Request $request) {
        $this->getSession();
        $slug = $request->segment(2);
        $pageInfo = DB::table('pages')->where('slug', $slug)->first();
        $pageTitle = $pageInfo->title;
        return view('pages.index', ['title' => $pageTitle, 'pageInfo' => $pageInfo]);
    }
    
    public function deleteAccount(Request $request){
        $pageTitle = 'Delete Account';
        return view('pages.deleteAccount', ['title' => $pageTitle]);
    }

    public function feedback(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Feedback');
        if (!empty($request->all())) {
            $request->validate([
                'email' => 'required|email',
                'subject' => 'required',
                'message' => 'required'
            ]);

            $userId = Session::get('user_id');
            $email = $request->get('email');
            $subject = $request->get('subject');
            $message = nl2br($request->get('message'));

            $serialisedData = array();
            $serialisedData['user_id'] = $userId;
            $serialisedData['email'] = $email;
            $serialisedData['subject'] = $subject;
            $serialisedData['message'] = $message;
            $serialisedData['created_at'] = date('Y-m-d H:i:s');
            Contact::insert($serialisedData);

//            $settings = DB::table('settings')->where('id', 1)->first();
//            $emailId = $settings->contact_email;
//
//            $emailTemplate = DB::table('emailtemplates')->where('id', 6)->first();
//            $toRepArray = array('[!name!]', '[!email!]', '[!contact!]', '[!message!]', '[!HTTP_PATH!]', '[!SITE_TITLE!]');
//            $fromRepArray = array($name, $email, $contact, $message, HTTP_PATH, SITE_TITLE);
//            $emailSubject = str_replace($toRepArray, $fromRepArray, $emailTemplate->subject);
//            $emailBody = str_replace($toRepArray, $fromRepArray, $emailTemplate->template);
//            Mail::to($emailId)->send(new SendMailable($emailBody, $emailSubject));

            Session::flash('success_message', __('message.Your feedback sent to us successfully, our team will contact you soon.'));
            return Redirect::to('feedback');
        }
        return view('pages.feedback', ['title' => $pageTitle]);
    }

}
