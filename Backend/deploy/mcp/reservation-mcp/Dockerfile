FROM node:24-alpine

WORKDIR /app

ENV NODE_ENV=production
ENV PORT=8765
ENV BASE_PATH=/mcp
ENV TRANSPORT=http

COPY package.json ./
COPY server.js ./

RUN npm install --omit=dev

EXPOSE 8765

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
  CMD node -e "fetch('http://127.0.0.1:'+(process.env.PORT||8765)+'/').then(()=>process.exit(0)).catch(()=>process.exit(1))"

CMD ["node", "server.js"]