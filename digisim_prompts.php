<?php

$prompts = [

    /*  
       PROMPT 1 – FOUNDATIONAL CONTEXT
     */
    'prompt1' => <<<'PROMPT'
    You are an expert in Business Continuity, Crisis Management, and Organizational Resilience, with experience designing simulation-based response exercises aligned to global good practices (ISO 22301, ISO 22316, NIST, and industry-recognized frameworks).

Your task is to design **content for a response simulation exercise** that prepares and assesses participants on **BC / crisis / resilience event or incident preparedness**.

### Exercise Purpose

The objective of this exercise is to evaluate:

1. The participant’s **knowledge of good practices** related to the {{scenario}} and {{objective}}.
2. The participant’s **ability to apply those good practices** effectively within a realistic, time-pressured simulation.

This is **not a theoretical test**. The focus is on decision-making, prioritization, preparedness actions, and response readiness.

### Simulation Context

* The exercise will be delivered through **simulation software**
* Participants will receive **scenario briefs and dynamic injects**
* Participants must make **explicit decisions or preparedness actions**
* Outcomes and feedback must reflect **alignment or misalignment with good practices**

### Design Requirements

When generating the exercise content:

* Generate all the content in professional {{language}} language.
* Base all expectations on **recognized good practices**, of BCM and Crisis  Management, not organization-specific policies unless stated
* Ensure the scenario is **realistic, internally consistent, and plausible**
* Clearly separate:

  
Acknowledge and Wait for further instructions before expanding the scenario or adding injects.

PROMPT,


    'prompt2' => <<<'PROMPT'
Create a **fictitious organization profile** that will serve as the base case for a Business Continuity and Crisis Response simulation exercise.

### Inputs 

* {{industry}}
* {{geography}}
* {{operating_scale}}

### Design Intent

The organization should appear **credible, operationally mature, and realistic**, suitable for a response preparedness simulation.

Assume that **reasonable continuity, incident, and crisis capabilities already exist** within the organization. These capabilities must be:

* **Implied through normal business language**
* Embedded naturally into how the organization operates
* Reflected in structure, operating model, dependencies, and governance
### Output Requirements

Generate:

1. A **company name** (fictitious)
2. A **clear, professional title**
3. A **concise introduction (maximum 200 words split as few logical paragraphs)** that covers:

   * What the company does
   * Where it operates
   * Why its operations are important or time-critical
   * Key dependencies (people, facilities, technology, suppliers, information)
   * An outline about the scenario/context based on the exercise objective and scenario


This company profile will be used as the **foundation for subsequent disruption scenarios and response decision-making**.
Return output strictly in JSON format:
{
  "company_name": "",
  "title": "",
  "introduction": ""
}



PROMPT
    ,

    /* =========================================================
       PROMPT 3 – EXTENDED PRACTICES
    ========================================================== */
    'prompt3' => <<<'PROMPT'
Design injects (message center inputs) for a simulation-based Business Continuity, Incident, and Crisis response exercise.
Purpose of Injects
Injects are used to simulate the flow of real-world information received by participants during an unfolding situation. Each inject should:
•	Provide data, reactions, updates, guidance, or noise
•	Influence situational awareness and decision-making
•	Create pressure, ambiguity, or competing priorities
•	Contribute to a coherent narrative within the stage
Injects should feel organic and realistic, not instructional.

Inject Structure
Each inject must include:
•	Media Type : {{injects}}
•	Subject: 5–7 words, concise and attention-grabbing
•	Body: 20–30 words maximum, written in natural business language
Avoid technical explanations, frameworks, or guidance language unless it fits the media type and sender.

Volume & Mix (Single Stage)
For a single stage:
•	Create the injects
•	Use a mix of media types : {{injects}}
•	Messages may come from different organizational stakeholders:

You may:
•	Use multiple injects from the same media type if contextually appropriate
•	Omit certain media types if they do not fit the situation
•	Vary tone and urgency across injects

Style Rules
•	Write as the sender, not as a narrator
•	Match tone to the media type (e.g., informal for chat, formal for regulator)
•	No “best practice” wording
•	No instructions to participants
•	No solution hints

Output Constraint
Generate only inject content.
Do not include analysis, explanations, scoring logic, or debrief notes.
Wait for further instruction before generating injects for additional stages.
Return output strictly in valid JSON array format.

Structure:
[
  {
    "media_type": "",
    "subject": "",
    "body": ""
  }
]

Do not include numbering.
Do not include markdown.
Do not include backticks.
Do not include explanations.
Return only valid JSON.


PROMPT
    ,

    /* =========================================================
       PROMPT 4 – ORGANIZATION PROFILE
    ========================================================== */
    'prompt4' => <<<'PROMPT'
Design response tasks for the exercise

Purpose of Response Tasks
Response tasks represent possible actions, decisions, or considerations a participant may choose in order to manage the situation presented in the current stage.
These tasks are used to assess the participant’s ability to:
•	Apply good practices appropriately to the context
•	Prioritize what truly matters for the exercise objective
•	Distinguish between critical actions, useful distractions, and misaligned responses

Response Set Structure
For this stage, generate exactly  {{score_value}} response statements based on “scale description” field

Writing Rules
•	Each response must be 15–25 words
•	Write in neutral, professional, action-oriented language
•	Responses should reflect applied good practices, but:
o	Do not reference frameworks, standards, or “best practices”
o	Do not signal correctness or priority
•	Avoid duplication or overlapping intent across responses

Contextual Alignment
•	Responses must align with:
o	The scenario and stage context
o	The information provided through injects
o	Realistic organizational behavior under pressure
•	Avoid “perfect world” assumptions
•	Some responses may involve uncertainty, sequencing, or dependency on incomplete information

Output Constraint
Provide only the response statements classified as {{score_value}}.
Do not include:
•	Labels or categories
•	Explanations or rationale
•	Scoring guidance
•	Hints about relevance

Each item must follow this structure:

[
  {
    "statement": "",
    "scale": ""
  }
]

Rules:
- The "scale" value must be exactly one of:
  {{score_value}}
- Each "statement" must contain 15–25 words.
- Do not include numbering.
- Do not include markdown.
- Do not include explanations.
- Do not include labels outside JSON.
- Return only valid JSON.
PROMPT
    ,

    //prompt5;
    'prompt5' => <<<'PROMPT'
    Create a de-briefing narrative
The de-brief is used to:
•	Help participants reflect on their choices
•	Clarify what an effective response looks like in this specific situation
•	Reinforce good practices through context, not theory
•	Connect decisions made during the exercise to real-world consequences
The tone should feel like an experienced facilitator or crisis leader walking participants through the situation after the event.

2. Key Learning Points (Bulleted) for the participants from the exercise. 

Output Constraint
Provide only:
•	One narrative paragraph
•	One structured set of learning bullets
Do not include:
•	Labels such as “correct answer”
•	References to participant scores
•	Step-by-step prescriptions
Wait for further instruction before generating de-briefs for additional stages.


3. Create the overall learning objective for a Business Continuity, Incident, and Crisis response simulation exercise.


Output Constraint
Provide only:
•	One narrative paragraph
•	One structured set of learning bullets
One structured set of learning bullets
Do not include:
•	Labels such as “correct answer”
•	References to participant scores
•	Step-by-step prescriptions
Wait for further instruction before generating de-briefs for additional stages.



PROMPT
,

    //prompt6;
    'prompt6' => <<<'PROMPT'
    Create a Moderator Manual for a Business Continuity, Incident, and Crisis response simulation exercise.
Purpose of the Manual
This manual is intended for facilitators/moderators who will run the simulation.
It must present all exercise content clearly, sequentially, and completely, enabling confident delivery without interpretation or improvisation.

Organize the manual using the following sections in this exact order:
1. Introduction to the Exercise
2. Exercise Outline
3. Learning Objective for the Participant
4. About the Company & Situation 
5. Stage Content
For each stage, create a clearly separated section with the following sub-sections:
a. Stage Briefing
Present the stage briefing exactly as provided.
b. Injects
c. Response Tasks
List all response statements for the stage, exactly as provided.

Output Constraint
Provide the complete moderator manual only, structured as specified above.
Do not include explanations of how the manual was created.


PROMPT,
//end above

    
];
