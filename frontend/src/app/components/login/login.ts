import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth';

@Component({
  selector: 'app-login',
  imports: [CommonModule, FormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class LoginComponent {
  mode: 'login' | 'register' = 'login';
  email    = '';
  password = '';
  loading  = false;
  error    = '';
  success  = '';

  constructor(private auth: AuthService, private router: Router) {}

  submit(): void {
    this.error   = '';
    this.success = '';
    this.loading = true;

    if (this.mode === 'login') {
      this.auth.login(this.email, this.password).subscribe({
        next: () => this.router.navigate(['/']),
        error: (err) => {
          this.loading = false;
          this.error = 'Invalid email or password. Please try again.';
        },
      });
    } else {
      this.auth.register(this.email, this.password).subscribe({
        next: () => {
          this.loading = false;
          this.success = 'Account created! You can now log in.';
          this.mode    = 'login';
          this.password = '';
        },
        error: (err) => {
          this.loading = false;
          this.error = err.error?.error ?? 'Registration failed.';
        },
      });
    }
  }

  switchMode(): void {
    this.mode    = this.mode === 'login' ? 'register' : 'login';
    this.error   = '';
    this.success = '';
  }
}
