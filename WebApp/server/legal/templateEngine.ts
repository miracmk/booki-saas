import { BookingContextData } from "@shared/legalTypes";
import { ContractCompilerService } from "./contractCompiler.service";

export class LegalTemplateEngine {
  public static render(templateMarkdown: string, context: BookingContextData, extraParams?: Record<string, any>): string {
    return ContractCompilerService.compile(templateMarkdown, context);
  }

  public static maskNationalId(tckn: string): string {
    return ContractCompilerService.maskTckn(tckn);
  }

  public static formatCurrency(amount: number, currency: string = "TRY"): string {
    return ContractCompilerService.formatCurrency(amount, currency);
  }

  public static formatDate(dateStr?: string): string {
    return ContractCompilerService.formatDateTime(dateStr);
  }
}
