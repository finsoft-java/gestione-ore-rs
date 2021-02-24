import { AuthenticationService } from './../_services/authentication.service';
import { Router } from '@angular/router';
import { FormGroup, FormControl, Validators, FormBuilder } from '@angular/forms';
import { Component, Input, OnInit, Output, EventEmitter } from '@angular/core';
import { first } from 'rxjs/operators';
@Component({
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent implements OnInit {
  error: string= '';
    messaggio_errore_login = false;

  constructor(public fb: FormBuilder,
    private router: Router,
    private authenticationService: AuthenticationService) {

    }
  form = this.fb.group({
    username: ['', [Validators.required]],
    password: ['', [Validators.required]]
  })

  // convenience getter for easy access to form fields
  get f() { return this.form.controls; }

  submit() {
    if (!this.form.invalid) {
      return this.authenticationService.login(this.f.username.value,this.f.password.value);
    }
  }


  ngOnInit(): void {
  }

}
