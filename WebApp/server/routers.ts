import { COOKIE_NAME } from "@shared/const";
import { getSessionCookieOptions } from "./_core/cookies";
import { systemRouter } from "./_core/systemRouter";
import { publicProcedure, router } from "./_core/trpc";
import { TRPCError } from "@trpc/server";
import { z } from "zod";
import { sendDemoRequestEmail } from "./demoEmail";
import { createZohoLead } from "./zohoCrm";

export const appRouter = router({
    // if you need to use socket.io, read and register route in server/_core/index.ts, all api should start with '/api/' so that the gateway can route correctly
  system: systemRouter,
  auth: router({
    me: publicProcedure.query(opts => opts.ctx.user),
    logout: publicProcedure.mutation(({ ctx }) => {
      const cookieOptions = getSessionCookieOptions(ctx.req);
      ctx.res.clearCookie(COOKIE_NAME, { ...cookieOptions, maxAge: -1 });
      return {
        success: true,
      } as const;
    }),
  }),

  demo: router({
    request: publicProcedure
      .input(z.object({
        name: z.string().trim().min(2).max(120),
        email: z.string().trim().email().max(320),
        phone: z.string().trim().regex(/^[0-9+ ()-]{10,20}$/),
        preferredContactTime: z.string().trim().min(3).max(40),
        businessType: z.string().trim().min(2).max(80),
      }))
      .mutation(async ({ input }) => {
        try {
          await sendDemoRequestEmail(input);
        } catch (error) {
          console.error("[Demo] Failed to send request email:", error);
          throw new TRPCError({ code: "INTERNAL_SERVER_ERROR", message: "Demo talebi gönderilemedi" });
        }

        // E-posta zaten birincil bildirim kanalı - Zoho CRM senkronu best-effort, başarısız
        // olsa da kullanıcıya gösterilen forma hata yansıtılmaz.
        createZohoLead({ ...input, source: "Demo Talebi" }).catch(error =>
          console.error("[Demo] Failed to sync Zoho CRM lead:", error),
        );

        return { success: true } as const;
      }),
  }),

  trial: router({
    request: publicProcedure
      .input(z.object({
        name: z.string().trim().min(2).max(120),
        email: z.string().trim().email().max(320),
        phone: z.string().trim().regex(/^[0-9+ ()-]{10,20}$/),
        preferredContactTime: z.string().trim().default("14 günlük deneme kaydı"),
        businessType: z.string().trim().min(2).max(80),
        plan: z.enum(["Enterprise", "Professional", "Starter"]),
      }))
      .mutation(async ({ input }) => {
        try {
          await sendDemoRequestEmail(input);
        } catch (error) {
          console.error("[Trial] Failed to send trial request email:", error);
          throw new TRPCError({ code: "INTERNAL_SERVER_ERROR", message: "Kayıt başlatılamadı" });
        }

        createZohoLead({ ...input, source: "Ücretsiz Deneme" }).catch(error =>
          console.error("[Trial] Failed to sync Zoho CRM lead:", error),
        );

        return { success: true } as const;
      }),
  }),

  // TODO: add feature routers here, e.g.
  // todo: router({
  //   list: protectedProcedure.query(({ ctx }) =>
  //     db.getUserTodos(ctx.user.id)
  //   ),
  // }),
});

export type AppRouter = typeof appRouter;
