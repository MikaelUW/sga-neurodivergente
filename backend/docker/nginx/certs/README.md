# Certificados TLS

O nginx de produção (`default.prod.conf`) espera dois arquivos **nesta pasta**:

- `fullchain.pem` — certificado + cadeia intermediária
- `privkey.pem` — chave privada

Nenhum deles é versionado (ver `.gitignore`).

## Produção — Let's Encrypt (recomendado)

Emita com o certbot no host e copie/monte os arquivos aqui, ou use um
proxy com ACME automático (Caddy/Traefik) na frente. Com certbot:

```bash
certbot certonly --standalone -d api.suaescola.exemplo
cp /etc/letsencrypt/live/api.suaescola.exemplo/fullchain.pem  ./fullchain.pem
cp /etc/letsencrypt/live/api.suaescola.exemplo/privkey.pem    ./privkey.pem
```

Programe a renovação (certbot renew) e recarregue o nginx após renovar.

## Homologação / teste local — certificado autoassinado

```bash
openssl req -x509 -nodes -newkey rsa:2048 -days 365 \
  -keyout privkey.pem -out fullchain.pem \
  -subj "/CN=localhost"
```

O navegador avisará que o certificado não é confiável (esperado em
autoassinado). Em produção use sempre um certificado emitido por uma CA.
