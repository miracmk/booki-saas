FROM node:24-alpine

ENV NODE_ENV=production
ENV PORT=3000
ENV SESSIONS_DIR=/app/sessions

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --omit=dev --no-audit --no-fund

COPY src ./src

EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD node -e "fetch('http://127.0.0.1:3000/health',{headers:{'X-Bridge-Secret':process.env.WA_BRIDGE_SECRET||''}}).then(r=>process.exit(r.ok?0:1)).catch(()=>process.exit(1))"

CMD ["node", "src/server.js"]