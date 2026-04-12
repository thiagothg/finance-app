import { z } from "zod";

export const BaseEntitySchema = z.object({
  id: z.string(),
  createdAt: z.string().datetime({ offset: true }).optional(),
  updatedAt: z.string().datetime({ offset: true }).optional(),
});

export type BaseEntity = z.infer<typeof BaseEntitySchema>;
