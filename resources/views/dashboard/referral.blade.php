@extends('layouts.home')
@section('content')
    <section class="banner-section">
        <style>
            body {
                margin: 0;
                padding: 20px;
            }

            .containers {
                background-color: #4b2e6f;
                border-radius: 20px;
                padding: 20px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                margin: 0px 14px 0px 14px;
                color: white;
            }

            .referral-stats p {
                margin: 10px 0;
            }

            .referral-link {
                margin: 20px 0;
            }

            .referral-link label {
                display: block;
                margin-bottom: 5px;
            }

            .referral-link input {
                width: calc(100% - 110px);
                padding: 10px;
                border: 1px solid #ccc;
                border-radius: 4px;
            }

            #copyFeedback {
                display: none;
                color: green;
                font-size: 14px;
                margin-top: 5px;
            }

            p {
                color: white;
            }
        </style>
        <div class="containers">
            <h3>{{__('message.Refer Friends & Earn Credit')}}</h3>
            <p>{{__('message.Introduce_friend.',['referral'=> "XAF $getBonusReferral->referralBonusSender" ?? 1,'signup'=> "XAF $getBonusReferral->referralBonusReceiver"  ?? 1])}}</p>
            

            <div class="referral-link">
                <label for="referralLink">{{__('message.Share my referral link with friends')}}</label>
                <div>
                    <input type="text" id="referralLink"
                        value="{{ url('/register?ref=' . (Auth::user()->referralCode ?? "")) }}" readonly>
                    <button type="button" class="btn btn-primary" onclick="copyToClipboard()" id="copyButton"
                        style="color: #fff;background-color: #654b86;border-color: #837e8b;padding: 9px;margin-top: -6px;">Copy</button>
                    <span id="copyFeedback"></span>
                </div>
            </div>
            <div class="social-buttons">
                <div class="sharethis-inline-share-buttons"
                    data-url="{{ url('/register?ref=' . (Auth::user()->referralCode ?? "")) }}"></div>
            </div>
        </div>
    </section>
    <section class="tiles-section-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="small-box bg-green">
                        <div class="inner">
                            <h3>{{ $successfulReferrals ?? 0 }}</h3>
                            <p>{{__('message.Successful Referrals')}}</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="small-box bg-green">
                        <div class="inner">
                            <h3>XAF {{$referralEarnings ?? 0}}</h3>
                            <p>{{__('message.Total Earnings from Referrals')}}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        function copyToClipboard() {
            var referralInput = document.getElementById("referralLink");
            var copyButton = document.getElementById("copyButton");
            var feedback = document.getElementById("copyFeedback");

            // Select and copy text
            referralInput.select();
            referralInput.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand("copy");

            // Change button text
            copyButton.innerText = "Copied!";
            feedback.style.display = 'inline';

            // Reset button text after 2 seconds
            setTimeout(function () {
                copyButton.innerText = "Copy";
                feedback.style.display = 'none';
            }, 2000);
        }
    </script>
@endsection