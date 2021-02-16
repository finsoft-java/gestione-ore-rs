export class User {
    username: string;
    nome: string;
    cognome: string;
    email: string;
    ruolo: string;
    ruolo_dec: string;
    from_ldap: boolean;
    nome_utente:string;
    from_ldap_dec: string;
    // Questi servono per gestire la modifica via web
    password?:string;  
    editing_psw?: boolean;  
    editing?: boolean;
    creating?: boolean;
}

export class UserRuoli {
    nominativo: string;
    nome_utente: string;
    responsabileL1: boolean;
    responsabileL2: boolean;
    utenteFinale: boolean;
    id_progetto: number;
    editing?: boolean;
}