FROM node:22-bookworm
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    git ca-certificates wget \
    chromium \
    xvfb xauth \
    libgtk2.0-0 libgtk-3-0 libgbm1 libnotify4 libnss3 libxss1 libasound2 \
    libxshmfence1 libatk-bridge2.0-0 libdrm2 libx11-xcb1 libxtst6 libxrandr2 \
    libpangocairo-1.0-0 fonts-liberation libappindicator3-1 \
 && rm -rf /var/lib/apt/lists/*

ENV CYPRESS_CACHE_FOLDER=/application/.cache/Cypress \
    XDG_CACHE_HOME=/application/.cache \
    npm_config_unsafe_perm=true

WORKDIR /application

CMD ["node", "-e", "console.log('Container ready')"]
