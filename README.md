**HOW TO INSTALL MINECRAFT WEB PANEL**

```bash
curl -fsSL https://raw.githubusercontent.com/joejoey2026king-star/minecraft-panel/refs/heads/main/install.sh | sudo bash
```

For automatic HTTPS, first point a DNS `A` record at the server IP, then run:

```bash
curl -fsSL https://raw.githubusercontent.com/joejoey2026king-star/minecraft-panel/refs/heads/main/install.sh | sudo PANEL_DOMAIN=minecraft.erwan.fun PANEL_EMAIL=erwan@gmail.com bash
```

Without `PANEL_DOMAIN`, the panel is installed on HTTP using the server IP.

To use trusted HTTPS directly on a public IP address, enable Let's Encrypt short-lived IP certificates:

```bash
curl -fsSL https://raw.githubusercontent.com/joejoey2026king-star/minecraft-panel/refs/heads/main/install.sh | sudo PANEL_IP_HTTPS=1 PANEL_EMAIL=erwan@gmail.com bash
```

IP certificates expire after about six days; the installer adds automatic daily renewal checking.
