<?php
namespace App\Http\Controllers\Admin; use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth;
class AuthController extends Controller {
 public function create(){return view('admin.auth.login');}
 public function store(Request $r){$cred=$r->validate(['email'=>['required','email'],'password'=>['required']]); if(Auth::attempt($cred,$r->boolean('remember'))){$r->session()->regenerate();return redirect()->intended(route('admin.dashboard'));} return back()->withErrors(['email'=>'Email or password is incorrect.'])->onlyInput('email');}
 public function destroy(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('admin.login');}
}
