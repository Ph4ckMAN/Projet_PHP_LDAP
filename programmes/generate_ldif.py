import csv
import secrets
import string
import subprocess
from unidecode import unidecode

BASE_DN = "dc=iut6-kourou,dc=fr"
GROUPS = ["BUT1RT", "BUT2RT", "BUT3RT", "BUT1GEII", "BUT2GEII", "BUT3GEII", "BUT1GCCD", "BUT2GCCD", "BUT3GCCD", "Etudiants", "Profs", "Administratif"]
PASSWORD_LENGTH = 10

def generate_password(length=PASSWORD_LENGTH):
    characters = string.ascii_letters + string.digits + string.punctuation
    return ''.join(secrets.choice(characters) for _ in range(length))

def hash_password_with_slappasswd(password: str) -> str:
    result = subprocess.run(['slappasswd', '-s', password], capture_output=True, text=True)
    return result.stdout.strip()

def generate_files():
    with open('./user.csv', 'r', encoding='utf-8') as csvfile, open('user.ldif', 'w', encoding='utf-8') as user_ldif, open('groupe.ldif', 'w', encoding='utf-8') as group_ldif, open('password.csv', 'w', encoding='utf-8') as pass_csv:
        reader = csv.reader(csvfile, delimiter=';')
        pass_writer = csv.writer(pass_csv)

        for group in GROUPS:
            group_ldif.write(f"dn: cn={group},ou=Groups,{BASE_DN}\n")
            group_ldif.write("changetype: add\n")
            group_ldif.write("objectClass: top\nobjectClass: posixGroup\n")
            group_ldif.write(f"cn: {group}\n")
            group_ldif.write(f"description: liste des membres du groupe {group}\n")
            group_ldif.write(f"gidNumber: {2000 + GROUPS.index(group)}\n\n")

        for row in reader:
            formation, nom, prenom, telephone = row
            nom = unidecode(nom)
            prenom = unidecode(prenom)
            login = f"{prenom}.{nom}".replace(" ", "_").lower()

            if 'Administratif' in formation:
                group_name = "Administratif"
            elif 'Enseignant' in formation:
                group_name = "Profs"
            else:
                group_name = "Etudiants"

            if group_name == "Administratif" or group_name == "Profs":
                email = f"{login}@iut6-kourou.fr"
            else:
                email = f"{login}@etu.iut6-kourou.fr"

            password = generate_password()
            hashed_password = hash_password_with_slappasswd(password)

            user_ldif.write(f"dn: uid={login},ou=People,{BASE_DN}\n")
            user_ldif.write("changetype: add\n")
            user_ldif.write("objectClass: top\nobjectClass: person\nobjectClass: inetOrgPerson\n")
            user_ldif.write(f"uid: {login}\n")
            user_ldif.write(f"sn: {nom.upper()}\n")
            user_ldif.write(f"gn: {prenom}\n")
            user_ldif.write(f"cn: {prenom} {nom.upper()}\n")
            user_ldif.write(f"mobile: {telephone}\n")
            user_ldif.write(f"mail: {email}\n")
            user_ldif.write(f"userPassword: {hashed_password}\n\n")

            pass_writer.writerow([login, password])

            group_ldif.write(f"dn: cn={group_name},ou=Groups,{BASE_DN}\n")
            group_ldif.write("changetype: modify\nadd: memberUid\n")
            group_ldif.write(f"memberUid: {login}\n\n")

            if 'BUT' in formation:
                group_name_formation = formation
                group_ldif.write(f"dn: cn={group_name_formation},ou=Groups,{BASE_DN}\n")
                group_ldif.write("changetype: modify\nadd: memberUid\n")
                group_ldif.write(f"memberUid: {login}\n\n")

generate_files()
