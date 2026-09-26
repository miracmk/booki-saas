# BooKi marketing website (booki.kibusiness.co) - standalone Node/Express + Vite React app,
# separate stack from the PHP monolith (ki-reservation-app). Build produces a static client
# bundle (dist/public) served by a small Express server (dist/index.js) with a tRPC API for the
# demo/trial request forms (email-only, no DB required - see server/routers.ts + demoEmail.ts).
FROM node:20-alpine AS build
WORKDIR /app
RUN corepack enable && corepack prepare pnpm@10.4.1 --activate
COPY package.json pnpm-lock.yaml ./
COPY patches/ ./patches/
RUN pnpm install --frozen-lockfile
COPY . .
RUN pnpm build

FROM node:20-alpine
WORKDIR /app
ENV NODE_ENV=production
RUN corepack enable && corepack prepare pnpm@10.4.1 --activate
# server/_core/vite.ts statically imports the "vite" package (only invoked in dev, but the
# import itself is evaluated at module load regardless of NODE_ENV) - a --prod-only install
# would make the bundled dist/index.js crash on startup with ERR_MODULE_NOT_FOUND for 'vite'.
COPY package.json pnpm-lock.yaml ./
COPY patches/ ./patches/
RUN pnpm install --frozen-lockfile
COPY --from=build /app/dist ./dist
EXPOSE 3000
CMD ["node", "dist/index.js"]
