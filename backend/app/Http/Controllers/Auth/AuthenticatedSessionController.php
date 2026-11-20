<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
class AuthenticatedSessionController extends Controller {
public function create(){return view('auth.login');}
public function store(Request $request){
$data=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
$key='login:'.strtolower($data['email']).'|'.$request->ip();
if(RateLimiter::tooManyAttempts($key,5)){throw ValidationException::withMessages(['email'=>'Too many login attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);}
if(!Auth::attempt($data,$request->boolean('remember'))){RateLimiter::hit($key,60);throw ValidationException::withMessages(['email'=>'The supplied credentials are incorrect.']);}
RateLimiter::clear($key);$request->session()->regenerate();return redirect()->intended(route('dashboard'));
}
public function destroy(Request $request){
Auth::guard('web')->logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect('/');
}
}
